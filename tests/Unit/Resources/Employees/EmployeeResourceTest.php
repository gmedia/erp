<?php

use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('employees');

test('toArray transforms employee correctly', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $employee = Employee::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'salary' => 75000.50,
        'hire_date' => '2023-03-15',
        'created_at' => '2023-01-10 14:30:00',
        'updated_at' => '2023-01-20 09:15:00',
    ]);

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKey('id', $employee->id)
        ->and($result)->toHaveKey('name', 'John Doe')
        ->and($result)->toHaveKey('email', 'john@example.com')
        ->and($result)->toHaveKey('phone', '555-1234')
        ->and($result['department'])->toBeArray()
        ->and($result['department']['id'])->toBe($department->id)
        ->and($result['department']['name'])->toBe($department->name)
        ->and($result['position'])->toBeArray()
        ->and($result['position']['id'])->toBe($position->id)
        ->and($result['position']['name'])->toBe($position->name)
        ->and($result)->toHaveKey('salary', '75000.50')
        ->and($result)->toHaveKey('hire_date')
        ->and($result)->toHaveKey('created_at')
        ->and($result)->toHaveKey('updated_at');
});

test('toArray includes all required fields', function () {
    $employee = Employee::factory()->create();

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys([
        'id', 'name', 'email', 'phone', 'department',
        'position', 'salary', 'hire_date', 'created_at', 'updated_at'
    ]);
});

test('toArray converts salary to string', function () {
    $employee = Employee::factory()->create(['salary' => 60000.75]);

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['salary'])->toBeString()
        ->and($result['salary'])->toBe('60000.75');
});

test('toArray formats hire_date as ISO8601 string', function () {
    $employee = Employee::factory()->create(['hire_date' => '2023-06-15']);

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['hire_date'])->toBeString()
        ->and($result['hire_date'])->toContain('2023-06-15');
});

test('toArray handles null phone field', function () {
    $employee = Employee::factory()->create(['phone' => null]);

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['phone'])->toBeNull();
});

test('toArray handles null timestamps', function () {
    $employee = Employee::factory()->create();
    $employee->created_at = null;
    $employee->updated_at = null;

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
