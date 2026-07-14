<?php

use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
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
        'created_at' => '2023-01-10 14:30:00',
        'updated_at' => '2023-01-20 09:15:00',
    ]);

    $employment = Employment::factory()->create([
        'employee_id' => $employee->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'salary' => 75000.50,
        'hire_date' => '2023-03-15',
        'is_current' => true,
    ]);

    $employee->load('currentEmployment');
    $employee->load('employments');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);
    $employment = $result['current_employment']->resolve();

    expect($result)->toHaveKey('id', $employee->id)
        ->and($result)->toHaveKey('name', 'John Doe')
        ->and($result)->toHaveKey('email', 'john@example.com')
        ->and($result)->toHaveKey('phone', '555-1234')
        ->and($result)->toHaveKey('employee_id')
        ->and($result)->toHaveKey('tenure')
        ->and($result)->toHaveKey('current_employment')
        ->and($employment)->toBeArray()
        ->and($employment['department_id'])->toBe($department->id)
        ->and($employment['position_id'])->toBe($position->id)
        ->and($employment['salary'])->toBeNumeric()
        ->and((float) $employment['salary'])->toEqual(75000.50)
        ->and($employment)->toHaveKey('hire_date')
        ->and($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});

test('toArray includes all required fields', function () {
    $employee = Employee::factory()->create();
    $employee->load('currentEmployment');
    $employee->load('employments');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys([
        'id', 'employee_id', 'name', 'email', 'phone', 'user_id',
        'tenure', 'current_employment', 'employments',
        'user', 'permissions', 'created_at', 'updated_at',
    ]);
});

test('toArray includes salary as numeric in current_employment', function () {
    $employee = Employee::factory()->create();
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'salary' => 60000.75,
        'is_current' => true,
    ]);
    $employee->load('currentEmployment');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);
    $employment = $result['current_employment']->resolve();

    expect($employment['salary'])->toBeNumeric()
        ->and((float) $employment['salary'])->toEqual(60000.75);
});

test('toArray formats hire_date in current_employment as ISO8601 string', function () {
    $employee = Employee::factory()->create();
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'hire_date' => '2023-06-15',
        'is_current' => true,
    ]);
    $employee->load('currentEmployment');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);
    $employment = $result['current_employment']->resolve();

    expect($employment['hire_date'])->toBeString()
        ->and($employment['hire_date'])->toContain('2023-06-15');
});

test('toArray handles null phone field', function () {
    $employee = Employee::factory()->create(['phone' => null]);
    $employee->load('currentEmployment');
    $employee->load('employments');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['phone'])->toBeNull();
});

test('toArray handles null timestamps', function () {
    $employee = Employee::factory()->create();
    $employee->created_at = null;
    $employee->updated_at = null;
    $employee->load('currentEmployment');
    $employee->load('employments');

    $resource = new EmployeeResource($employee);
    $request = new Request;

    $result = $resource->toArray($request);

    expect($result['created_at'])->toBeNull()
        ->and($result['updated_at'])->toBeNull();
});
