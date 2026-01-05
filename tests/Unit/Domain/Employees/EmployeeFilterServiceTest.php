<?php

use App\Domain\Employees\EmployeeFilterService;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('applySearch adds where clause for search term', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'John Doe']);
    Employee::factory()->create(['name' => 'Jane Smith']);
    Employee::factory()->create(['email' => 'bob@example.com']);

    $query = Employee::query();
    $service->applySearch($query, 'john', ['name', 'email']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('applyAdvancedFilters applies department filter', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['department' => 'Engineering']);
    Employee::factory()->create(['department' => 'Marketing']);
    Employee::factory()->create(['department' => 'Sales']);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, ['department' => 'Engineering']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->department)->toBe('Engineering');
});

test('applyAdvancedFilters applies position filter', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['position' => 'Developer']);
    Employee::factory()->create(['position' => 'Manager']);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, ['position' => 'Developer']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->position)->toBe('Developer');
});

test('applyAdvancedFilters applies salary range filters', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['salary' => 50000.00]);
    Employee::factory()->create(['salary' => 75000.00]);
    Employee::factory()->create(['salary' => 100000.00]);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, [
        'salary_min' => 60000,
        'salary_max' => 90000,
    ]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->salary)->toBe('75000.00');
});

test('applyAdvancedFilters applies hire date range filters', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['hire_date' => '2023-01-01']);
    Employee::factory()->create(['hire_date' => '2023-06-01']);
    Employee::factory()->create(['hire_date' => '2023-12-01']);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, [
        'hire_date_from' => '2023-03-01',
        'hire_date_to' => '2023-09-01',
    ]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->hire_date->format('Y-m-d'))->toBe('2023-06-01');
});

test('applyAdvancedFilters handles empty filters', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->count(3)->create();

    $query = Employee::query();
    $originalCount = $query->count();

    $service->applyAdvancedFilters($query, []);

    expect($query->count())->toBe($originalCount);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'Z Employee']);
    Employee::factory()->create(['name' => 'A Employee']);

    $query = Employee::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Employee')
        ->and($results->last()->name)->toBe('Z Employee');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'A Employee']);
    Employee::factory()->create(['name' => 'Z Employee']);

    $query = Employee::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Employee')
        ->and($results->last()->name)->toBe('A Employee');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'Test Employee']);

    $query = Employee::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
