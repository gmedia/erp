<?php

use App\Models\{Asset, Employee, Permission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    Asset::factory()->count(3)->create();

    $response = $this->getJson(route('assets.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
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
    
    $this->assertDatabaseHas('assets', ['asset_code' => $data['asset_code']]);
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

test('can delete asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->deleteJson(route('assets.destroy', $asset));

    $response->assertStatus(200);
    $this->assertSoftDeleted('assets', ['id' => $asset->id]);
});
