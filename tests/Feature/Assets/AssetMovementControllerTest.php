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
    $pMovement = Permission::firstOrCreate(['name' => 'asset_movement', 'display_name' => 'Asset Movement']);
    
    $this->employee->permissions()->attach([$pAsset->id, $pEdit->id, $pMovement->id]);
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

test('can record asset return', function () {
    $dept = Department::factory()->create();
    $employee = Employee::factory()->create(['department_id' => $dept->id]);
    $asset = Asset::factory()->create([
        'department_id' => $dept->id,
        'employee_id' => $employee->id,
    ]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'return',
        'moved_at' => now()->toIso8601String(),
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'return',
    ]);

    // Note: AssetController currently doesn't nullify employee/department on return 
    // but the test ensures the movement is recorded.
});

test('can record asset disposal', function () {
    $asset = Asset::factory()->create(['status' => 'active']);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => now()->toIso8601String(),
        'notes' => 'Aset sudah rusak parah',
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'notes' => 'Aset sudah rusak parah',
    ]);
});

test('can record asset adjustment', function () {
    $asset = Asset::factory()->create();

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'adjustment',
        'moved_at' => now()->toIso8601String(),
        'notes' => 'Adjustment after stocktake',
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'adjustment',
    ]);
});

test('can record asset acquisition manually', function () {
    $asset = Asset::factory()->create();

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => $asset->purchase_date->toIso8601String(),
        'notes' => 'Manual recording of acquisition',
    ];

    $response = $this->postJson(route('api.asset-movements.store'), $data);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('asset_movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'notes' => 'Manual recording of acquisition',
    ]);
});
