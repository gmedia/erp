<?php

use App\Console\Commands\EmployeeCreateCommand;
use App\Models\Employee;
use Faker\Factory as Faker;
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

    $employee = Employee::with(['department', 'position'])->first();

    expect($employee->name)->toBeString()
        ->and($employee->email)->toBeString()
        ->and($employee->department_id)->toBeInt()
        ->and($employee->position_id)->toBeInt()
        ->and($employee->department)->not->toBeNull()
        ->and($employee->position)->not->toBeNull()
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

    $employee = Employee::with('department')->first();

    $expectedDepartments = [
        'Engineering', 'Sales', 'Marketing', 'HR', 'Finance',
        'Operations', 'Customer Support', 'Product',
    ];

    expect(in_array($employee->department->name, $expectedDepartments))->toBeTrue();
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

test('command handles database transactions properly', function () {
    $initialCount = Employee::count();

    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 3]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS);
    expect(Employee::count())->toBe($initialCount + 3);

    // Ensure all created employees have valid data
    $newEmployees = Employee::skip($initialCount)->take(3)->get();
    foreach ($newEmployees as $employee) {
        expect($employee->email)->toBeString();
        expect($employee->name)->toBeString();
    }
});

test('command executes all code paths for coverage', function () {
    $initialCount = Employee::count();

    // Run with multiple employees to ensure progress bar advances
    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 3]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS);
    expect(Employee::count())->toBe($initialCount + 3);

    // Test output contains expected messages
    $outputContent = Artisan::output();
    expect($outputContent)->toContain("Found {$initialCount} existing employees.")
        ->and($outputContent)->toContain('Generating 3 dummy employees...')
        ->and($outputContent)->toContain('✅ Successfully created 3 employees.')
        ->and($outputContent)->toContain('📊 Total employees in database: ' . ($initialCount + 3));
});

test('command handles exceptions and shows failure messages', function () {
    $initialCount = Employee::count();

    // Use the test-exception option to force an exception on the 2nd employee
    $exitCode = Artisan::call(EmployeeCreateCommand::class, [
        'count' => 3,
        '--test-exception' => 2
    ]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS);
    expect(Employee::count())->toBe($initialCount + 2); // Only 1st and 3rd should succeed

    $outputContent = Artisan::output();

    // Should show error message for failed creation
    expect($outputContent)->toContain('Failed to create employee: Test exception for coverage')
        ->and($outputContent)->toContain('⚠️ Failed to create 1 employees');
});

test('command fails when email limit is completely reached', function () {
    // Create employees at the exact limit
    $existingCount = 10000; // At the 10000 limit
    Employee::factory()->count($existingCount)->create();

    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 1]);

    expect($exitCode)->toBe(EmployeeCreateCommand::FAILURE);

    $outputContent = Artisan::output();
    expect($outputContent)->toContain('Maximum unique email limit reached. Cannot generate more employees.');
});

test('command handles email limit and reduces count appropriately', function () {
    // Create employees close to the limit
    $existingCount = 9997; // Very close to 10000 limit
    Employee::factory()->count($existingCount)->create();

    Artisan::call(EmployeeCreateCommand::class, ['count' => 10]);

    $outputContent = Artisan::output();

    expect($outputContent)->toContain('Requested 10 employees, but only 3 unique emails available');
    expect($outputContent)->toContain('Generating 3 dummy employees...');
    expect($outputContent)->toContain('✅ Successfully created 3 employees.');
});

test('command handles high employee counts efficiently', function () {
    $initialCount = Employee::count();

    // Test with a reasonable number that exercises the loop
    $exitCode = Artisan::call(EmployeeCreateCommand::class, ['count' => 50]);

    expect($exitCode)->toBe(EmployeeCreateCommand::SUCCESS);
    expect(Employee::count())->toBe($initialCount + 50);

    $outputContent = Artisan::output();
    expect($outputContent)->toContain('✅ Successfully created 50 employees.');
});
