<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(RefreshDatabase::class)->group('assets');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset', 'asset.create', 'asset.edit', 'asset.delete']);
    actingAs($user);
});

test('it returns asset index', function () {
    Asset::factory()->count(3)->create();

    $response = getJson('/api/assets');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('it stores new asset', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();

    $data = [
        'asset_code' => 'AST-001',
        'name' => 'Test Asset',
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'purchase_date' => '2023-01-01',
        'purchase_cost' => 1000000,
        'currency' => 'IDR',
        'status' => 'active',
        'depreciation_method' => 'straight_line',
    ];

    $response = postJson('/api/assets', $data);

    $response->assertStatus(201);
    assertDatabaseHas('assets', ['asset_code' => 'AST-001']);
});

test('it shows asset', function () {
    $asset = Asset::factory()->create();

    $response = getJson("/api/assets/{$asset->ulid}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $asset->id);
});

test('it updates existing asset', function () {
    $asset = Asset::factory()->create();

    $response = putJson("/api/assets/{$asset->ulid}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(200);
    assertDatabaseHas('assets', [
        'id' => $asset->id,
        'name' => 'Updated Name',
    ]);
});

test('it soft deletes asset', function () {
    $asset = Asset::factory()->create();

    $response = deleteJson("/api/assets/{$asset->ulid}");

    $response->assertStatus(200);
    assertSoftDeleted('assets', ['id' => $asset->id]);
});

test('it syncs initial movement during asset creation', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();

    $data = [
        'asset_code' => 'AST-002',
        'name' => 'Asset with Movement',
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'purchase_date' => '2023-05-01',
        'purchase_cost' => 5000000,
        'currency' => 'IDR',
        'status' => 'active',
        'depreciation_method' => 'straight_line',
    ];

    postJson('/api/assets', $data);

    $asset = Asset::where('asset_code', 'AST-002')->first();

    assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => '2023-05-01 00:00:00',
        'to_branch_id' => $branch->id,
    ]);
});

test('it syncs initial movement when purchase_cost or purchase_date changes during update', function () {
    $asset = Asset::factory()->create([
        'purchase_date' => '2023-01-01',
        'purchase_cost' => 1000000,
    ]);

    // Update case in AssetController uses updateOrCreate with acquired type
    putJson("/api/assets/{$asset->ulid}", [
        'purchase_date' => '2023-02-01',
        'purchase_cost' => 2000000,
    ]);

    assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => '2023-02-01 00:00:00',
    ]);
});

test('it does not change movement when only name changes during update', function () {
    $asset = Asset::factory()->create([
        'purchase_date' => '2023-01-01',
        'purchase_cost' => 1000000,
    ]);

    // Ensure movement exists via controller or direct
    AssetMovement::create([
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => '2023-01-01 00:00:00',
        'to_branch_id' => $asset->branch_id,
    ]);

    putJson("/api/assets/{$asset->ulid}", [
        'name' => 'Just a name change',
    ]);

    assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => '2023-01-01 00:00:00',
    ]);
});

test('it exports assets', function () {
    Asset::factory()->count(5)->create();

    $response = postJson('/api/assets/export');

    $response->assertStatus(200)
        ->assertJsonStructure(['url', 'filename']);
});

test('it exports assets with filters', function () {
    $branch = Branch::factory()->create();
    $category = AssetCategory::factory()->create();
    Asset::factory()->create(['branch_id' => $branch->id]);
    Asset::factory()->create(['asset_category_id' => $category->id]);

    // Filter by branch
    $response = postJson("/api/assets/export", ['branch_id' => $branch->id]);
    $response->assertStatus(200);

    // Filter by category
    $response = postJson("/api/assets/export", ['asset_category_id' => $category->id]);
    $response->assertStatus(200);

    // Filter by status
    $response = postJson("/api/assets/export", ['status' => 'active']);
    $response->assertStatus(200);
});
