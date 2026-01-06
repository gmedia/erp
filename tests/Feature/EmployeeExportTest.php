<?php

use App\Exports\EmployeeExport;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EmployeeExport', function () {

    test('query applies search filter across name and email fields', function () {
        Employee::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Employee::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Employee::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $export = new EmployeeExport(['search' => 'doe']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Doe');
    });

    test('query applies exact department filter', function () {
        Employee::factory()->create(['department' => 'engineering']);
        Employee::factory()->create(['department' => 'marketing']);
        Employee::factory()->create(['department' => 'sales']);

        $export = new EmployeeExport(['department' => 'engineering']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->department)->toBe('engineering');
    });

    test('query applies exact position filter', function () {
        Employee::factory()->create(['position' => 'Senior Developer']);
        Employee::factory()->create(['position' => 'Product Manager']);
        Employee::factory()->create(['position' => 'Designer']);

        $export = new EmployeeExport(['position' => 'Senior Developer']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->position)->toBe('Senior Developer');
    });

    test('query applies minimum salary filter', function () {
        Employee::factory()->create(['salary' => 45000.00]);
        Employee::factory()->create(['salary' => 65000.00]);
        Employee::factory()->create(['salary' => 85000.00]);

        $export = new EmployeeExport(['min_salary' => 60000]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $salaries = $results->pluck('salary')->map(fn($s) => (float)$s)->sort()->values();
        expect($salaries[0])->toBeGreaterThanOrEqual(65000.00)
            ->and($salaries[1])->toBeGreaterThanOrEqual(65000.00);
    });

    test('query applies maximum salary filter', function () {
        Employee::factory()->create(['salary' => 45000.00]);
        Employee::factory()->create(['salary' => 65000.00]);
        Employee::factory()->create(['salary' => 85000.00]);

        $export = new EmployeeExport(['max_salary' => 70000]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $salaries = $results->pluck('salary')->map(fn($s) => (float)$s)->sort()->values();
        expect($salaries[0])->toBeLessThanOrEqual(65000.00)
            ->and($salaries[1])->toBeLessThanOrEqual(65000.00);
    });

    test('query applies both min and max salary range filters', function () {
        Employee::factory()->create(['salary' => 40000.00]);
        Employee::factory()->create(['salary' => 60000.00]);
        Employee::factory()->create(['salary' => 80000.00]);
        Employee::factory()->create(['salary' => 100000.00]);

        $export = new EmployeeExport([
            'min_salary' => 55000,
            'max_salary' => 90000,
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $salaries = $results->pluck('salary')->map(fn($s) => (float)$s)->sort()->values();
        expect($salaries[0])->toBe(60000.00)
            ->and($salaries[1])->toBe(80000.00);
    });

    test('query applies hire date from filter', function () {
        Employee::factory()->create(['hire_date' => '2023-01-01']);
        Employee::factory()->create(['hire_date' => '2023-06-01']);
        Employee::factory()->create(['hire_date' => '2023-12-01']);

        $export = new EmployeeExport(['hire_date_from' => '2023-03-01']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $hireDates = $results->pluck('hire_date')->map(fn($d) => $d->format('Y-m-d'))->sort()->values();
        expect($hireDates)->toContain('2023-06-01', '2023-12-01');
    });

    test('query applies hire date to filter', function () {
        Employee::factory()->create(['hire_date' => '2023-01-01']);
        Employee::factory()->create(['hire_date' => '2023-06-01']);
        Employee::factory()->create(['hire_date' => '2023-12-01']);

        $export = new EmployeeExport(['hire_date_to' => '2023-09-01']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $hireDates = $results->pluck('hire_date')->map(fn($d) => $d->format('Y-m-d'))->sort()->values();
        expect($hireDates)->toContain('2023-01-01', '2023-06-01');
    });

    test('query applies hire date range filters', function () {
        Employee::factory()->create(['hire_date' => '2023-01-01']);
        Employee::factory()->create(['hire_date' => '2023-06-01']);
        Employee::factory()->create(['hire_date' => '2023-12-01']);

        $export = new EmployeeExport([
            'hire_date_from' => '2023-03-01',
            'hire_date_to' => '2023-09-01',
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->hire_date->format('Y-m-d'))->toBe('2023-06-01');
    });

    test('query applies ascending sort by name', function () {
        Employee::factory()->create(['name' => 'Zoe Employee']);
        Employee::factory()->create(['name' => 'Alice Employee']);
        Employee::factory()->create(['name' => 'Bob Employee']);

        $export = new EmployeeExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alice Employee')
            ->and($results[1]->name)->toBe('Bob Employee')
            ->and($results[2]->name)->toBe('Zoe Employee');
    });

    test('query applies descending sort by salary', function () {
        Employee::factory()->create(['name' => 'Low Salary', 'salary' => 40000.00]);
        Employee::factory()->create(['name' => 'High Salary', 'salary' => 80000.00]);
        Employee::factory()->create(['name' => 'Medium Salary', 'salary' => 60000.00]);

        $export = new EmployeeExport(['sort_by' => 'salary', 'sort_direction' => 'desc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('High Salary')
            ->and($results[1]->name)->toBe('Medium Salary')
            ->and($results[2]->name)->toBe('Low Salary');
    });

    test('query does not allow invalid sort columns', function () {
        Employee::factory()->create(['name' => 'Test Employee']);

        $export = new EmployeeExport(['sort_by' => 'invalid_column']);

        $query = $export->query();

        // Should not throw error, just ignore invalid sort
        expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        expect($query->get())->toHaveCount(1);
    });

    test('query combines multiple filters correctly', function () {
        Employee::factory()->create([
            'name' => 'John Developer',
            'department' => 'engineering',
            'position' => 'Senior Developer',
            'salary' => 75000.00,
        ]);
        Employee::factory()->create([
            'name' => 'Jane Manager',
            'department' => 'marketing',
            'position' => 'Marketing Manager',
            'salary' => 65000.00,
        ]);
        Employee::factory()->create([
            'name' => 'Bob Developer',
            'department' => 'engineering',
            'position' => 'Developer',
            'salary' => 55000.00,
        ]);

        $export = new EmployeeExport([
            'department' => 'engineering',
            'min_salary' => 60000,
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Developer');
    });

    test('headings returns correct column headers', function () {
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

    test('map transforms employee data correctly with all fields', function () {
        $employee = Employee::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'department' => 'engineering',
            'position' => 'Senior Developer',
            'salary' => 85000.50,
            'hire_date' => '2023-03-15',
            'created_at' => '2023-01-10 14:30:00',
        ]);

        $export = new EmployeeExport([]);
        $mapped = $export->map($employee);

        expect($mapped)->toBe([
            $employee->id,
            'John Doe',
            'john@example.com',
            '555-1234',
            'engineering',
            'Senior Developer',
            '85000.50',
            '2023-03-15',
            '2023-01-10 14:30:00',
        ]);
    });

    test('map handles null phone field', function () {
        $employee = Employee::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => null,
            'department' => 'marketing',
            'position' => 'Manager',
            'salary' => 70000.00,
            'hire_date' => '2023-02-01',
        ]);

        $export = new EmployeeExport([]);
        $mapped = $export->map($employee);

        expect($mapped[3])->toBeNull(); // Phone field should be null
    });

    test('handles empty filters gracefully', function () {
        Employee::factory()->count(5)->create();

        $export = new EmployeeExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(5);
    });

});
