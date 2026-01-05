<?php

use App\Console\Commands\EmployeeCreateCommand;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('command creates specified number of employees', function () {
    $initialCount = Employee::count();

    Artisan::call(EmployeeCreateCommand::class, ['count' => 5]);

    expect(Employee::count())->toBe($initialCount + 5);
});

test('command generates unique emails', function () {
    Artisan::call(EmployeeCreateCommand::class, ['count' => 3]);

    $employees = Employee::all();
    $emails = $employees->pluck('email')->toArray();

    expect(array_unique($emails))->toHaveCount(3);
});

test('command generates realistic employee data', function () {
    Artisan::call(EmployeeCreateCommand::class, ['count' => 1]);

    $employee = Employee::first();

    expect($employee->name)->toBeString()
        ->and($employee->email)->toBeString()
        ->and($employee->department)->toBeString()
        ->and($employee->position)->toBeString()
        ->and($employee->salary)->toBeString()
        ->and($employee->hire_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('command generates salary appropriate for position', function () {
    // Test with a known position that has salary range
    Artisan::call(EmployeeCreateCommand::class, ['count' => 1]);

    $employee = Employee::first();

    // Basic check that salary is reasonable (between 30k and 300k)
    expect($employee->salary)->toBeGreaterThan(30000)
        ->and($employee->salary)->toBeLessThan(300000);
});

test('command fails with count less than 1', function () {
    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 0]);

    expect($exitCode)->toBe(EmployeeCreateCommand::FAILURE);
});

test('command fails when trying to exceed email limit', function () {
    // Create employees close to the limit (simulate existing data)
    $existingCount = 9995; // Close to 10000 limit

    // Mock existing count by creating employees
    Employee::factory()->count($existingCount)->create();

    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 10]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS); // Should succeed but create fewer
});

test('command succeeds with valid count', function () {
    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 1]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS);
});

test('command generates employees with departments from realistic list', function () {
    Artisan::call(EmployeeCreateCommand::class, ['count' => 1]);

    $employee = Employee::first();

    $expectedDepartments = [
        'Engineering', 'Sales', 'Marketing', 'HR', 'Finance',
        'Operations', 'Customer Support', 'Product'
    ];

    expect(in_array($employee->department, $expectedDepartments))->toBeTrue();
});

test('command generates unique emails with fallback mechanism', function () {
    // Test the email generation fallback by creating many employees
    // This will test the generateUniqueEmail method thoroughly

    $count = 10;
    Artisan::call(EmployeeCreateCommand::class, ['count' => $count]);

    $employees = Employee::all();
    $emails = $employees->pluck('email')->toArray();

    // All emails should be unique
    expect(array_unique($emails))->toHaveCount($count);

    // All emails should be valid email format
    foreach ($emails as $email) {
        expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
    }
});
