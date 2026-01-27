<?php

use App\Exports\EmployeeExport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('employees');

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
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $sales = Department::factory()->create(['name' => 'Sales']);

        Employee::factory()->create(['department_id' => $engineering->id]);
        Employee::factory()->create(['department_id' => $marketing->id]);
        Employee::factory()->create(['department_id' => $sales->id]);

        $export = new EmployeeExport(['department' => $engineering->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->department->name)->toBe('Engineering');
    });

    test('query applies exact position filter', function () {
        $seniorDev = Position::factory()->create(['name' => 'Senior Developer']);
        $productManager = Position::factory()->create(['name' => 'Product Manager']);
        $designer = Position::factory()->create(['name' => 'Designer']);

        Employee::factory()->create(['position_id' => $seniorDev->id]);
        Employee::factory()->create(['position_id' => $productManager->id]);
        Employee::factory()->create(['position_id' => $designer->id]);

        $export = new EmployeeExport(['position' => $seniorDev->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->position->name)->toBe('Senior Developer');
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
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $seniorDev = Position::factory()->create(['name' => 'Senior Developer']);
        $marketingManager = Position::factory()->create(['name' => 'Marketing Manager']);
        $developer = Position::factory()->create(['name' => 'Developer']);

        Employee::factory()->create([
            'name' => 'John Developer',
            'department_id' => $engineering->id,
            'position_id' => $seniorDev->id,
            'salary' => 75000.00,
        ]);
        Employee::factory()->create([
            'name' => 'Jane Manager',
            'department_id' => $marketing->id,
            'position_id' => $marketingManager->id,
            'salary' => 65000.00,
        ]);
        Employee::factory()->create([
            'name' => 'Bob Developer',
            'department_id' => $engineering->id,
            'position_id' => $developer->id,
            'salary' => 55000.00,
        ]);

        $export = new EmployeeExport([
            'department' => $engineering->id,
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
            'Branch',
            'Salary',
            'Hire Date',
            'Created At',
        ]);
    });

    test('map transforms employee data correctly with all fields', function () {
        $department = Department::factory()->create(['name' => 'Engineering']);
        $position = Position::factory()->create(['name' => 'Senior Developer']);

        $employee = Employee::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'department_id' => $department->id,
            'position_id' => $position->id,
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
            'Engineering',
            'Senior Developer',
            $employee->branch->name,
            '85000.50',
            '2023-03-15',
            '2023-01-10T14:30:00+00:00',
        ]);
    });

    test('map handles null phone field', function () {
        $department = Department::factory()->create(['name' => 'Marketing']);
        $position = Position::factory()->create(['name' => 'Manager']);

        $employee = Employee::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
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
