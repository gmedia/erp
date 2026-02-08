<?php

use App\Models\{Asset, AssetMovement, Branch, Department, Employee, Permission, User, AssetLocation};
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
    $pEdit = Permission::firstOrCreate(['name' => 'asset.edit', 'display_name' => 'Edit Asset']);
    
    $this->employee->permissions()->attach([$pAsset->id, $pEdit->id]);
});

test('can list asset movements', function () {
    $asset = Asset::factory()->create();
    AssetMovement::create([
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now(),
        'from_branch_id' => $asset->branch_id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson(route('api.asset-movements.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('can record asset transfer', function () {
    $asset = Asset::factory()->create();
    $newBranch = Branch::factory()->create();
    $newLocation = AssetLocation::factory()->create(['branch_id' => $newBranch->id]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->toIso8601String(),
        'to_branch_id' => $newBranch->id,
        'to_location_id' => $newLocation->id,
        'reference' => 'REF-001',
        'notes' => 'Transferring for project X',
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'to_branch_id' => $newBranch->id,
    ]);

    // Check if asset current state is updated
    $asset->refresh();
    expect($asset->branch_id)->toBe($newBranch->id);
    expect($asset->asset_location_id)->toBe($newLocation->id);
});

test('can record asset assignment', function () {
    $asset = Asset::factory()->create();
    $newDept = Department::factory()->create();
    $newEmployee = Employee::factory()->create(['department_id' => $newDept->id]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'assign',
        'moved_at' => now()->toIso8601String(),
        'to_department_id' => $newDept->id,
        'to_employee_id' => $newEmployee->id,
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'assign',
        'to_employee_id' => $newEmployee->id,
    ]);

    // Check if asset current state is updated
    $asset->refresh();
    expect($asset->department_id)->toBe($newDept->id);
    expect($asset->employee_id)->toBe($newEmployee->id);
});

test('validates required fields for asset movements', function () {
    $response = $this->postJson(route('api.asset-movements.store'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['asset_id', 'movement_type', 'moved_at']);
});

test('can list asset movements with asset_id filter', function () {
    $asset1 = Asset::factory()->create();
    $asset2 = Asset::factory()->create();
    
    AssetMovement::create([
        'asset_id' => $asset1->id,
        'movement_type' => 'transfer',
        'moved_at' => now(),
        'from_branch_id' => $asset1->branch_id,
        'created_by' => $this->user->id,
    ]);
    
    AssetMovement::create([
        'asset_id' => $asset2->id,
        'movement_type' => 'assign',
        'moved_at' => now(),
        'from_branch_id' => $asset2->branch_id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson(route('api.asset-movements.index', ['asset_id' => $asset1->id]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('cannot record asset movement without permission', function () {
    // Create user without edit permission
    $user = User::factory()->create();
    $this->actingAs($user);
    $employee = Employee::factory()->create(['user_id' => $user->id, 'email' => $user->email]);
    $pAsset = Permission::firstOrCreate(['name' => 'asset', 'display_name' => 'Asset']);
    $employee->permissions()->attach([$pAsset->id]);

    $asset = Asset::factory()->create();
    $newBranch = Branch::factory()->create();
    $newLocation = AssetLocation::factory()->create(['branch_id' => $newBranch->id]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->toIso8601String(),
        'to_branch_id' => $newBranch->id,
        'to_location_id' => $newLocation->id,
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(403);
});

test('requires destination branch for transfer', function () {
    $asset = Asset::factory()->create();
    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->toIso8601String(),
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['to_branch_id', 'to_location_id']);
});
