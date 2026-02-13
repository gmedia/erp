<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-movements');

test('it can list asset movements', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    $movements = AssetMovement::factory()->count(3)->create();

    $response = $this->actingAs($user)
        ->get(route('asset-movements.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('asset-movements/index')
            ->has('movements.data', 3)
        );
});

test('it can filter asset movements', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    
    AssetMovement::factory()->create(['reference' => 'REF-A']);
    AssetMovement::factory()->create(['reference' => 'REF-B']);

    $response = $this->actingAs($user)
        ->get(route('asset-movements.index', ['search' => 'REF-A']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('movements.data', 1)
            ->where('movements.data.0.reference', 'REF-A')
        );
});

test('it can create an asset movement', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    $asset = Asset::factory()->create();
    $targetBranch = Branch::factory()->create();
    $targetLocation = AssetLocation::factory()->create();

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => '2023-01-01',
        'to_branch_id' => $targetBranch->id,
        'to_location_id' => $targetLocation->id,
        'reference' => 'MOV-NEW',
        'notes' => 'Transferring asset',
    ];

    $response = $this->actingAs($user)
        ->post(route('api.asset-movements.store'), $data);

    $response->assertCreated();
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'reference' => 'MOV-NEW',
    ]);

    // Verify asset state updated
    $asset->refresh();
    expect($asset->branch_id)->toBe($targetBranch->id);
    expect($asset->asset_location_id)->toBe($targetLocation->id);
});

test('it can show an asset movement', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    $movement = AssetMovement::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('api.asset-movements.show', $movement));

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'asset_id', 'movement_type']]);
});

test('it can update an asset movement', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    $movement = AssetMovement::factory()->create();

    $data = [
        'moved_at' => '2023-02-01',
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('api.asset-movements.update', $movement), $data);

    $response->assertOk();

    $this->assertDatabaseHas('asset_movements', [
        'id' => $movement->id,
        'notes' => 'Updated notes',
    ]);
});

test('it can delete the latest movement and revert asset state', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    
    // Initial state
    $initialBranch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $initialBranch->id]);
    
    // First movement (should preserve initial state in 'from' fields essentially by logic)
    // But create logic sets 'from' based on current asset state.
    
    // Create first movement (e.g. initial assign)
    $targetBranch1 = Branch::factory()->create();
    $movement1 = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'moved_at' => now()->subDay(),
        'from_branch_id' => $initialBranch->id,
        'to_branch_id' => $targetBranch1->id,
    ]);
    // Apply update manually as factory doesn't trigger controller logic
    $asset->update(['branch_id' => $targetBranch1->id]);

    // Create second (latest) movement
    $targetBranch2 = Branch::factory()->create();
    $movement2 = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'moved_at' => now(),
        'from_branch_id' => $targetBranch1->id,
        'to_branch_id' => $targetBranch2->id,
    ]);
    $asset->update(['branch_id' => $targetBranch2->id]);

    // Delete latest movement
    $response = $this->actingAs($user)
        ->delete(route('api.asset-movements.destroy', $movement2));

    $response->assertOk();
    $this->assertDatabaseMissing('asset_movements', ['id' => $movement2->id]);

    // Asset should revert to state of movement1 (to_branch_id)
    $asset->refresh();
    expect($asset->branch_id)->toBe($targetBranch1->id);
});

test('it can delete an older movement WITHOUT reverting asset state', function () {
    $user = createTestUserWithPermissions(['asset_movement']);
    
    $asset = Asset::factory()->create();
    
    // Older movement
    $movement1 = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'moved_at' => now()->subDay(),
    ]);
    
    // Latest movement
    $movement2 = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'moved_at' => now(), 
    ]);

    // Current asset location set to latest
    $asset->update(['branch_id' => $movement2->to_branch_id]);

    // Delete older movement
    $response = $this->actingAs($user)
        ->delete(route('api.asset-movements.destroy', $movement1));

    $response->assertOk();
    
    $this->assertDatabaseMissing('asset_movements', ['id' => $movement1->id]);

    // Asset should remained unchanged (matching movement2)
    $asset->refresh();
    expect($asset->branch_id)->toBe($movement2->to_branch_id);
});
