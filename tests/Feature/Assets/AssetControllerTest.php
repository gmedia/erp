<?php

use App\Models\{Asset, AssetMovement, Employee, Permission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'email' => $this->user->email,
    ]);

    $pAsset = Permission::firstOrCreate(['name' => 'asset', 'display_name' => 'Asset']);
    $pCreate = Permission::firstOrCreate(['name' => 'asset.create', 'display_name' => 'Create Asset']);
    $pEdit = Permission::firstOrCreate(['name' => 'asset.edit', 'display_name' => 'Edit Asset']);
    $pDelete = Permission::firstOrCreate(['name' => 'asset.delete', 'display_name' => 'Delete Asset']);
    
    $this->employee->permissions()->attach([$pAsset->id, $pCreate->id, $pEdit->id, $pDelete->id]);
});

test('can list assets', function () {
    $baseline = Asset::count();
    Asset::factory()->count(3)->create();

    $response = $this->getJson(route('assets.index'));

    $response->assertStatus(200)
        ->assertJsonCount($baseline + 3, 'data');
});

test('can create asset', function () {
    $asset = Asset::factory()->make();
    $data = $asset->toArray();
    
    // Ensure dates are strings for JSON request
    $data['purchase_date'] = $asset->purchase_date->format('Y-m-d');
    if ($asset->warranty_end_date) {
        $data['warranty_end_date'] = $asset->warranty_end_date->format('Y-m-d');
    }

    $response = $this->postJson(route('assets.store'), $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', $data['name']);
    
    $assetId = $response->json('data.id');
    
    $this->assertDatabaseHas('assets', ['asset_code' => $data['asset_code']]);
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $assetId,
        'movement_type' => 'acquired',
        'to_branch_id' => $data['branch_id'],
        'to_location_id' => $data['asset_location_id'] ?? null,
    ]);
});

test('can update asset', function () {
    $asset = Asset::factory()->create();
    $newData = ['name' => 'Updated Asset Name'];

    $response = $this->putJson(route('assets.update', $asset), $newData);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Asset Name');
    
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'name' => 'Updated Asset Name'
    ]);
});

test('updating asset syncs existing acquired movement', function () {
    $asset = Asset::factory()->create(['name' => 'Original Name']);
    
    // Manually create the initial 'acquired' movement
    AssetMovement::create([
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => $asset->purchase_date,
        'to_branch_id' => $asset->branch_id,
        'to_location_id' => $asset->asset_location_id,
        'created_by' => $this->user->id,
    ]);

    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
    ]);

    $newData = [
        'name' => 'Updated Name',
        'purchase_date' => now()->subDays(10)->format('Y-m-d'),
    ];

    $response = $this->putJson(route('assets.update', $asset), $newData);

    $response->assertStatus(200);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => $newData['purchase_date'] . ' 00:00:00',
    ]);
});

test('updating asset creates acquired movement if missing', function () {
    // Manually create asset without movement (simulating old data)
    $asset = Asset::factory()->create();
    AssetMovement::where('asset_id', $asset->id)->delete();
    
    $this->assertDatabaseMissing('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
    ]);

    $newData = ['name' => 'Updated Name'];
    $response = $this->putJson(route('assets.update', $asset), $newData);

    $response->assertStatus(200);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
    ]);
});

test('can delete asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->deleteJson(route('assets.destroy', $asset));

    $response->assertStatus(200);
    $this->assertSoftDeleted('assets', ['id' => $asset->id]);
});
