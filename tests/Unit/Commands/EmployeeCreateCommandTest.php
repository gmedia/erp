<?php

use App\Console\Commands\EmployeeCreateCommand;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('generateUniqueEmail creates unique emails', function () {
    $command = new EmployeeCreateCommand();

    // Use reflection to access private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateUniqueEmail');
    $method->setAccessible(true);

    $faker = \Faker\Factory::create();

    // Generate a few emails
    $email1 = $method->invoke($command, $faker);
    $email2 = $method->invoke($command, $faker);

    // Ensure they are unique
    expect($email1)->not->toBe($email2);

    // Ensure they are valid email format
    expect(filter_var($email1, FILTER_VALIDATE_EMAIL))->toBeTruthy();
    expect(filter_var($email2, FILTER_VALIDATE_EMAIL))->toBeTruthy();
});

test('generateUniqueEmail handles existing emails by generating new ones', function () {
    // Create an existing employee with a specific email
    Employee::factory()->create(['email' => 'test@example.com']);

    $command = new EmployeeCreateCommand();

    // Use reflection to access private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateUniqueEmail');
    $method->setAccessible(true);

    // Create a simple faker instance - the method will handle the uniqueness internally
    $faker = \Faker\Factory::create();

    // Generate an email - it should keep trying until it finds a unique one
    $email = $method->invoke($command, $faker);

    // The email should be different from the existing one
    expect($email)->not->toBe('test@example.com');

    // Should be valid email format
    expect(filter_var($email, FILTER_VALIDATE_EMAIL))->toBeTruthy();
});

test('generateSalaryForPosition returns appropriate salary ranges', function () {
    $command = new EmployeeCreateCommand();

    // Use reflection to access private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateSalaryForPosition');
    $method->setAccessible(true);

    $faker = \Faker\Factory::create();

    // Test different positions that exist in the salary ranges
    $positions = [
        'Software Engineer' => [50000, 90000],
        'Senior Software Engineer' => [80000, 130000],
        'Operations Manager' => [60000, 95000],
    ];

    foreach ($positions as $position => $expectedRange) {
        $salary = (float) $method->invoke($command, $position, $faker);

        // Salary should be numeric and within expected range
        expect($salary)->toBeGreaterThanOrEqual($expectedRange[0]);
        expect($salary)->toBeLessThanOrEqual($expectedRange[1]);
    }
});

test('generateSalaryForPosition handles unknown positions with default range', function () {
    $command = new EmployeeCreateCommand();

    // Use reflection to access private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateSalaryForPosition');
    $method->setAccessible(true);

    $faker = \Faker\Factory::create();

    // Test with unknown position
    $salary = $method->invoke($command, 'Unknown Position', $faker);

    // Should still return a valid salary (default range 40000-80000)
    expect($salary)->toBeNumeric();
    expect($salary)->toBeGreaterThan(30000);
    expect($salary)->toBeLessThan(90000);
});

test('generateSalaryForPosition formats salary correctly', function () {
    $command = new EmployeeCreateCommand();

    // Use reflection to access private method
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateSalaryForPosition');
    $method->setAccessible(true);

    $faker = \Faker\Factory::create();

    // Mock faker to return a predictable value
    $fakerMock = \Mockery::mock($faker);
    $fakerMock->shouldReceive('numberBetween')
        ->with(50000, 90000)
        ->andReturn(75000);

    $salary = $method->invoke($command, 'Software Engineer', $fakerMock);

    // Should be formatted as string with 2 decimal places
    expect($salary)->toBe('75000.00');
});
