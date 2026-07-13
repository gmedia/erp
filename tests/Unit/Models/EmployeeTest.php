<?php

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('employees');

test('factory creates a valid employee', function () {
    $employee = Employee::factory()->create();

    assertDatabaseHas('employees', ['id' => $employee->id]);

    expect($employee->getAttributes())->toMatchArray([
        'name' => $employee->name,
        'email' => $employee->email,
        'phone' => $employee->phone,
        'employee_id' => $employee->employee_id,
        'user_id' => $employee->user_id,
    ]);

});

test('employee model casts are applied correctly', function () {
    $employee = Employee::factory()->create();

    expect($employee->created_at)->toBeInstanceOf(Carbon::class)
        ->and($employee->updated_at)->toBeInstanceOf(Carbon::class);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new Employee)->getFillable();

    expect($fillable)->toBe([
        'employee_id',
        'name',
        'email',
        'phone',
        'user_id',
    ]);
});
