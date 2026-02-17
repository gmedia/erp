<?php

namespace Tests\Feature\AssetMovements;

use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-movements');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset_movement', 'asset_movement.create', 'asset_movement.edit', 'asset_movement.delete']);
    actingAs($user);
});

test('it returns asset movement index', function () {
    AssetMovement::factory()->count(3)->create();

    $response = getJson('/api/asset-movements');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('it stores new asset movement', function () {
    $asset = Asset::factory()->create();
    $toBranch = Branch::factory()->create();
    $toLocation = AssetLocation::factory()->create(['branch_id' => $toBranch->id]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'to_branch_id' => $toBranch->id,
        'to_location_id' => $toLocation->id,
        'reference' => 'MOV-001',
        'notes' => 'Test transfer',
    ];

    $response = postJson('/api/asset-movements', $data);

    $response->assertStatus(201);
    assertDatabaseHas('asset_movements', ['reference' => 'MOV-001']);
});

test('it shows asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = getJson("/api/asset-movements/{$movement->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $movement->id);
});

test('it updates existing asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = putJson("/api/asset-movements/{$movement->id}", [
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Updated notes',
    ]);

    $response->assertStatus(200);
    assertDatabaseHas('asset_movements', [
        'id' => $movement->id,
        'notes' => 'Updated notes',
    ]);
});

test('it deletes asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = deleteJson("/api/asset-movements/{$movement->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('asset_movements', ['id' => $movement->id]);
});
