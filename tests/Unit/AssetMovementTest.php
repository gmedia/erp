<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\AssetLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

test('it has fillable attributes', function () {
    $model = new AssetMovement();
    expect($model->getFillable())->toBe([
        'asset_id',
        'movement_type',
        'moved_at',
        'from_branch_id',
        'to_branch_id',
        'from_location_id',
        'to_location_id',
        'from_department_id',
        'to_department_id',
        'from_employee_id',
        'to_employee_id',
        'reference',
        'notes',
        'created_by',
    ]);
});

test('it belongs to an asset', function () {
    $movement = AssetMovement::factory()->create();
    expect($movement->asset)->toBeInstanceOf(Asset::class);
});

test('it belongs to branches', function () {
    $movement = AssetMovement::factory()->create();
    if ($movement->from_branch_id) {
        expect($movement->fromBranch)->toBeInstanceOf(Branch::class);
    }
    if ($movement->to_branch_id) {
        expect($movement->toBranch)->toBeInstanceOf(Branch::class);
    }
});

test('it belongs to departments', function () {
    $movement = AssetMovement::factory()->create();
    if ($movement->from_department_id) {
        expect($movement->fromDepartment)->toBeInstanceOf(Department::class);
    }
    if ($movement->to_department_id) {
        expect($movement->toDepartment)->toBeInstanceOf(Department::class);
    }
});

test('it belongs to employees', function () {
    $movement = AssetMovement::factory()->create();
    if ($movement->from_employee_id) {
        expect($movement->fromEmployee)->toBeInstanceOf(Employee::class);
    }
    if ($movement->to_employee_id) {
        expect($movement->toEmployee)->toBeInstanceOf(Employee::class);
    }
});

test('it belongs to locations', function () {
    $movement = AssetMovement::factory()->create();
    if ($movement->from_location_id) {
        expect($movement->fromLocation)->toBeInstanceOf(AssetLocation::class);
    }
    if ($movement->to_location_id) {
        expect($movement->toLocation)->toBeInstanceOf(AssetLocation::class);
    }
});

test('it belongs to a creator', function () {
    $movement = AssetMovement::factory()->create();
    expect($movement->createdBy)->toBeInstanceOf(User::class);
});
