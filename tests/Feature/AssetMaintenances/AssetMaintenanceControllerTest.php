<?php

namespace Tests\Feature\AssetMaintenances;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-maintenances');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset_maintenance', 'asset_maintenance.create', 'asset_maintenance.edit', 'asset_maintenance.delete']);
    actingAs($user);
});

test('it returns asset maintenance index', function () {
    AssetMaintenance::factory()->count(3)->create();

    $response = getJson('/api/asset-maintenances');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('it stores new asset maintenance', function () {
    $asset = Asset::factory()->create();
    $supplier = Supplier::factory()->create();

    $data = [
        'asset_id' => $asset->id,
        'maintenance_type' => 'preventive',
        'status' => 'scheduled',
        'scheduled_at' => now()->format('Y-m-d H:i:s'),
        'supplier_id' => $supplier->id,
        'cost' => 100000,
        'notes' => 'Test maintenance',
    ];

    $response = postJson('/api/asset-maintenances', $data);

    $response->assertStatus(201);
    assertDatabaseHas('asset_maintenances', ['notes' => 'Test maintenance']);
});

test('it shows asset maintenance', function () {
    $maintenance = AssetMaintenance::factory()->create();

    $response = getJson("/api/asset-maintenances/{$maintenance->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $maintenance->id);
});

test('it updates existing asset maintenance', function () {
    $maintenance = AssetMaintenance::factory()->create();

    $response = putJson("/api/asset-maintenances/{$maintenance->id}", [
        'status' => 'completed',
        'performed_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Updated notes',
    ]);

    $response->assertStatus(200);

    assertDatabaseHas('asset_maintenances', [
        'id' => $maintenance->id,
        'notes' => 'Updated notes',
        'status' => 'completed',
    ]);
});

test('it deletes asset maintenance', function () {
    $maintenance = AssetMaintenance::factory()->create();

    $response = deleteJson("/api/asset-maintenances/{$maintenance->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('asset_maintenances', ['id' => $maintenance->id]);
});
