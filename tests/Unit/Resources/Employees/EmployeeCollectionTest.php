<?php

use App\Http\Resources\Employees\EmployeeCollection;
use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Models\Employment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('employees');

test('collects property is set correctly', function () {
    $collection = new EmployeeCollection([]);

    expect($collection->collects)->toBe(EmployeeResource::class);
});

test('collection transforms multiple employees correctly', function () {
    $employees = Employee::factory()->count(3)->create();

    // Ensure each employee has a current employment for salary assertion
    foreach ($employees as $employee) {
        Employment::factory()->create([
            'employee_id' => $employee->id,
            'salary' => 50000.00,
            'is_current' => true,
        ]);
    }

    $collection = new EmployeeCollection($employees);
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);

    foreach ($result as $index => $item) {
        expect($item)->toHaveKeys([
            'id', 'employee_id', 'name', 'email', 'phone', 'user_id',
            'tenure', 'current_employment', 'employments',
            'created_at', 'updated_at',
        ])
            ->and($item['id'])->toBe($employees[$index]->id)
            ->and($item['name'])->toBe($employees[$index]->name)
            ->and($item['email'])->toBe($employees[$index]->email)
            ->and($item['current_employment']['salary'])->toBeString();
    }
});

test('collection returns empty array when no employees', function () {
    $collection = new EmployeeCollection(collect());
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});
