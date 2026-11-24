<?php

namespace Tests\Feature\AssetMovements;

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\AssetMovement;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-movements');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'asset_movement',
        'asset_movement.create',
        'asset_movement.edit',
        'asset_movement.delete',
    ]);
    Sanctum::actingAs($user);
});

test('dispose movement sets asset status to disposed in the same transaction', function () {
    $asset = Asset::factory()->create(['status' => 'active']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'disposed']);
});

test('second dispose of the same asset is rejected with 422', function () {
    $asset = Asset::factory()->create(['status' => 'active']);
    $payload = [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ];

    postJson('/api/asset-movements', $payload)->assertStatus(201);
    postJson('/api/asset-movements', $payload)->assertStatus(422);

    expect(AssetMovement::where('asset_id', $asset->id)->where('movement_type', 'dispose')->count())->toBe(1);
});

test('non-disposal movement for a disposed asset is rejected with 422', function () {
    $asset = Asset::factory()->create(['status' => 'disposed']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'adjustment',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(422);

    expect(AssetMovement::where('asset_id', $asset->id)->count())->toBe(0);
});

test('non-disposal movement for a lost asset is rejected with 422', function () {
    $asset = Asset::factory()->create(['status' => 'lost']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'to_branch_id' => $asset->branch_id,
        'to_location_id' => $asset->asset_location_id,
    ])->assertStatus(422);

    expect(AssetMovement::where('asset_id', $asset->id)->count())->toBe(0);
});

test('deleting the dispose movement restores the asset status', function () {
    $asset = Asset::factory()->create(['status' => 'active']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    $movement = AssetMovement::where('asset_id', $asset->id)->firstOrFail();
    deleteJson("/api/asset-movements/{$movement->id}")->assertStatus(200);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'active']);
});

test('deleting a dispose movement restores the status the asset had before disposal', function () {
    $asset = Asset::factory()->create(['status' => 'maintenance']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    $movement = AssetMovement::where('asset_id', $asset->id)->firstOrFail();
    deleteJson("/api/asset-movements/{$movement->id}")->assertStatus(200);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'maintenance']);
});

test('deleting a legacy dispose movement without a saved status restores active', function () {
    $asset = Asset::factory()->create(['status' => 'disposed']);
    $movement = AssetMovement::create([
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now(),
        'from_branch_id' => $asset->branch_id,
        'from_location_id' => $asset->asset_location_id,
        'from_department_id' => $asset->department_id,
        'from_employee_id' => $asset->employee_id,
        'created_by' => null,
        'status_before_disposal' => null,
    ]);

    deleteJson("/api/asset-movements/{$movement->id}")->assertStatus(200);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'active']);
});

test('changing the movement type of a dispose movement is rejected with 422', function () {
    $asset = Asset::factory()->create(['status' => 'active']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    $movement = AssetMovement::where('asset_id', $asset->id)->firstOrFail();
    putJson("/api/asset-movements/{$movement->id}", [
        'movement_type' => 'adjustment',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(422)->assertJsonValidationErrors('movement_type');

    expect($movement->fresh()->movement_type)->toBe('dispose');
    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'disposed']);
});

test('moving a dispose movement to another asset is rejected with 422', function () {
    $asset = Asset::factory()->create(['status' => 'active']);
    $other = Asset::factory()->create(['status' => 'active']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    $movement = AssetMovement::where('asset_id', $asset->id)->firstOrFail();
    putJson("/api/asset-movements/{$movement->id}", [
        'asset_id' => $other->id,
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(422)->assertJsonValidationErrors('asset_id');

    expect($movement->fresh()->asset_id)->toBe($asset->id);
    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'disposed']);
    assertDatabaseHas('assets', ['id' => $other->id, 'status' => 'active']);
});

test('a transfer movement cannot be moved to another asset or type through PUT', function () {
    $asset = Asset::factory()->create(['status' => 'active']);
    $other = Asset::factory()->create(['status' => 'active']);
    $movement = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
    ]);

    putJson("/api/asset-movements/{$movement->id}", [
        'asset_id' => $other->id,
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(422)->assertJsonValidationErrors('asset_id');

    putJson("/api/asset-movements/{$movement->id}", [
        'movement_type' => 'assign',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(422)->assertJsonValidationErrors('movement_type');

    expect($movement->fresh()->asset_id)->toBe($asset->id);
    expect($movement->fresh()->movement_type)->toBe('transfer');
});

test('a transfer movement accepts PUT that repeats its current asset and type', function () {
    $asset = Asset::factory()->create(['status' => 'active']);
    $movement = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
    ]);

    putJson("/api/asset-movements/{$movement->id}", [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'tanpa perubahan aset dan tipe',
    ])->assertStatus(200);
});

test('editing a dispose movement cannot leave the asset status stale', function () {
    $asset = Asset::factory()->create(['status' => 'active']);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->format('Y-m-d H:i:s'),
    ])->assertStatus(201);

    $movement = AssetMovement::where('asset_id', $asset->id)->firstOrFail();
    putJson("/api/asset-movements/{$movement->id}", [
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'koreksi catatan',
    ])->assertStatus(200);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'disposed']);
});

test('depreciation run does not count an asset disposed in the period', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'open',
    ]);
    $asset = Asset::factory()->create([
        'status' => 'active',
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2024-01-01',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'accumulated_depreciation' => 0,
        'book_value' => 12000000,
    ]);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => '2024-03-01 00:00:00',
    ])->assertStatus(201);

    Sanctum::actingAs(createTestUserWithPermissions(['asset_depreciation_run', 'asset_depreciation_run.edit']));
    $response = postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2024-03-01',
        'period_end' => '2024-03-31',
    ]);

    $response->assertStatus(200);
    expect(AssetDepreciationRun::count())->toBe(1);
    expect(AssetDepreciationLine::where('asset_id', $asset->id)->count())->toBe(0);
});
