<?php

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('factory creates a valid employee', function () {
    $employee = Employee::factory()->create();

    assertDatabaseHas('employees', ['id' => $employee->id]);

    expect($employee->getAttributes())->toMatchArray([
        'name' => $employee->name,
        'email' => $employee->email,
        'phone' => $employee->phone,
        'department_id' => $employee->department_id,
        'position_id' => $employee->position_id,
        'salary' => $employee->salary,
        'hire_date' => $employee->hire_date,
    ]);
});

test('casts are applied correctly', function () {
    $employee = Employee::factory()->create([
        'salary' => 12345.67,
        'hire_date' => '2023-04-01',
    ]);

    // salary should be a stringified decimal with two places
    expect($employee->salary)->toBeString()
        ->and($employee->salary)->toBe('12345.67');

    // hire_date should be a Carbon instance
    expect($employee->hire_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Employee)->getFillable();

    expect($fillable)->toBe([
        'name',
        'email',
        'phone',
        'department_id',
        'position_id',
        'user_id',
        'salary',
        'hire_date',
    ]);
});
