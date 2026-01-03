<?php

use App\Exports\EmployeeExport;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('employee export query applies search filter', function () {
    Employee::factory()->create(['name' => 'John Doe']);
    Employee::factory()->create(['email' => 'jane@example.com']);
    Employee::factory()->create(['name' => 'Bob Smith']);

    $export = new EmployeeExport(['search' => 'john']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('employee export query applies department filter', function () {
    Employee::factory()->create(['department' => 'Engineering']);
    Employee::factory()->create(['department' => 'Marketing']);
    Employee::factory()->create(['department' => 'Sales']);

    $export = new EmployeeExport(['department' => 'Engineering']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->department)->toBe('Engineering');
});

test('employee export query applies position filter', function () {
    Employee::factory()->create(['position' => 'Developer']);
    Employee::factory()->create(['position' => 'Manager']);

    $export = new EmployeeExport(['position' => 'Developer']);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->position)->toBe('Developer');
});

test('employee export query applies salary range filters', function () {
    Employee::factory()->create(['salary' => 50000.00]);
    Employee::factory()->create(['salary' => 75000.00]);
    Employee::factory()->create(['salary' => 100000.00]);

    $export = new EmployeeExport([
        'min_salary' => 60000,
        'max_salary' => 90000
    ]);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->salary)->toBe('75000.00');
});

test('employee export query applies hire date range filters', function () {
    Employee::factory()->create(['hire_date' => '2023-01-01']);
    Employee::factory()->create(['hire_date' => '2023-06-01']);
    Employee::factory()->create(['hire_date' => '2023-12-01']);

    $export = new EmployeeExport([
        'hire_date_from' => '2023-03-01',
        'hire_date_to' => '2023-09-01'
    ]);

    $query = $export->query();

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->hire_date->format('Y-m-d'))->toBe('2023-06-01');
});

test('employee export query applies sorting', function () {
    Employee::factory()->create(['name' => 'Z Employee']);
    Employee::factory()->create(['name' => 'A Employee']);

    $export = new EmployeeExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

    $query = $export->query();

    $results = $query->get();

    expect($results->first()->name)->toBe('A Employee')
        ->and($results->last()->name)->toBe('Z Employee');
});

test('employee export query does not allow invalid sort columns', function () {
    Employee::factory()->create(['name' => 'Test Employee']);

    $export = new EmployeeExport(['sort_by' => 'invalid_column']);

    $query = $export->query();

    // Should not throw error, just ignore invalid sort
    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('employee export headings are correct', function () {
    $export = new EmployeeExport([]);

    $headings = $export->headings();

    expect($headings)->toBe([
        'ID',
        'Name',
        'Email',
        'Phone',
        'Department',
        'Position',
        'Salary',
        'Hire Date',
        'Created At',
    ]);
});

test('employee export map transforms data correctly', function () {
    $employee = Employee::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'department' => 'Engineering',
        'position' => 'Developer',
        'salary' => 75000.00,
        'hire_date' => '2023-01-15',
        'created_at' => '2023-01-01 10:00:00',
    ]);

    $export = new EmployeeExport([]);
    $mapped = $export->map($employee);

    expect($mapped)->toBe([
        $employee->id,
        'John Doe',
        'john@example.com',
        '555-1234',
        'Engineering',
        'Developer',
        75000.00,
        '2023-01-15',
        '2023-01-01 10:00:00',
    ]);
});
