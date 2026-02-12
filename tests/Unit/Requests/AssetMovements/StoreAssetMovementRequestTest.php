<?php

namespace Tests\Unit\Requests\AssetMovements;

use App\Http\Requests\AssetMovements\StoreAssetMovementRequest;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

function createStoreRequest(array $data = []): StoreAssetMovementRequest
{
    $request = new StoreAssetMovementRequest();
    $request->merge($data);
    return $request;
}

function assertStoreValidationPasses($data)
{
    $validator = validator($data, createStoreRequest($data)->rules());
    expect($validator->fails())->toBeFalse('Validation should have passed but failed: ' . implode(', ', $validator->errors()->all()));
}

function assertStoreValidationFails($data, $expectedErrors = [])
{
    $validator = validator($data, createStoreRequest($data)->rules());
    expect($validator->fails())->toBeTrue('Validation should have failed but passed.');
    
    foreach ($expectedErrors as $error) {
        expect($validator->errors()->has($error))->toBeTrue("Validation should have error for key: $error");
    }
}

test('it authorizes request', function () {
    $request = createStoreRequest();
    expect($request->authorize())->toBeTrue();
});

test('it validates basic rules', function () {
    $asset = Asset::factory()->create();

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'acquired',
        'moved_at' => '2023-01-01',
        'notes' => 'Some notes',
    ];

    assertStoreValidationPasses($data);

    // Required fields
    assertStoreValidationFails([], ['asset_id', 'movement_type', 'moved_at']);

    // Enums
    assertStoreValidationFails(['movement_type' => 'invalid'], ['movement_type']);
});

test('it validates conditional transfer rules', function () {
    $asset = Asset::factory()->create();
    
    // Transfer requires to_branch_id and to_location_id
    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => '2023-01-01',
    ];

    assertStoreValidationFails($data, ['to_branch_id', 'to_location_id']);

    $branch = Branch::factory()->create();
    $location = AssetLocation::factory()->create();

    $validData = array_merge($data, [
        'to_branch_id' => $branch->id,
        'to_location_id' => $location->id,
    ]);

    assertStoreValidationPasses($validData);
});

test('it validates conditional assign rules', function () {
    $asset = Asset::factory()->create();
    
    // Assign requires to_department_id and to_employee_id
    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'assign',
        'moved_at' => '2023-01-01',
    ];

    assertStoreValidationFails($data, ['to_department_id', 'to_employee_id']);

    $department = Department::factory()->create();
    $employee = Employee::factory()->create();

    $validData = array_merge($data, [
        'to_department_id' => $department->id,
        'to_employee_id' => $employee->id,
    ]);

    assertStoreValidationPasses($validData);
});
