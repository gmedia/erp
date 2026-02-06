<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets', 'asset-feature');

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

    $response = $this->getJson('/api/assets');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can store asset', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();
    
    $data = [
        'asset_code' => 'NEW-ASSET-001',
        'name' => 'New Laptop',
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'purchase_date' => '2023-01-01',
        'purchase_cost' => 15000000,
        'currency' => 'IDR',
        'status' => 'draft',
        'depreciation_method' => 'straight_line',
    ];

    $response = $this->postJson('/api/assets', $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.asset_code', 'NEW-ASSET-001');
        
    $this->assertDatabaseHas('assets', ['asset_code' => 'NEW-ASSET-001']);
});

test('can show asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->getJson("/api/assets/{$asset->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $asset->id);
});

test('can update asset', function () {
    $asset = Asset::factory()->create(['name' => 'Old Name']);
    
    $response = $this->putJson("/api/assets/{$asset->id}", [
        'name' => 'Updated Name'
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'name' => 'Updated Name'
    ]);
});

test('can delete asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->deleteJson("/api/assets/{$asset->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('assets', ['id' => $asset->id]);
});
