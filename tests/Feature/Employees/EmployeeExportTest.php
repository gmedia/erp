<?php

use App\Exports\EmployeeExport;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('employees');

describe('EmployeeExport', function () {
    beforeEach(function () {
        Employee::query()->delete();
    });

    test('query applies search filter across name and email fields', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        Employee::factory()
            ->afterCreating(function ($e) use ($company, $department, $position) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => $department->id,
                    'position_id' => $position->id, 'company_id' => $company->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Employee::factory()
            ->afterCreating(function ($e) use ($company, $department, $position) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => $department->id,
                    'position_id' => $position->id, 'company_id' => $company->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Employee::factory()
            ->afterCreating(function ($e) use ($company, $department, $position) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => $department->id,
                    'position_id' => $position->id, 'company_id' => $company->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $export = new EmployeeExport(['search' => 'doe']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('John Doe');
    });

    test('query applies exact department filter', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $sales = Department::factory()->create(['name' => 'Sales']);

        Employee::factory()
            ->afterCreating(function ($e) use ($engineering) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $engineering->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) use ($marketing) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $marketing->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) use ($sales) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $sales->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['department_id' => $engineering->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->currentEmployment->department->name)->toBe('Engineering');
    });

    test('query applies exact position filter', function () {
        $seniorDev = Position::factory()->create(['name' => 'Senior Developer']);
        $productManager = Position::factory()->create(['name' => 'Product Manager']);
        $designer = Position::factory()->create(['name' => 'Designer']);

        Employee::factory()
            ->afterCreating(function ($e) use ($seniorDev) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => $seniorDev->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) use ($productManager) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => $productManager->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) use ($designer) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => $designer->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['position_id' => $seniorDev->id]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->currentEmployment->position->name)->toBe('Senior Developer');
    });

    test('query applies minimum salary filter', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 45000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 65000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 85000.00,
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['salary_min' => 60000]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $salaries = $results->map(fn ($e) => (float) $e->currentEmployment->salary)->sort()->values();
        expect($salaries[0])->toBeGreaterThanOrEqual(65000.00)
            ->and($salaries[1])->toBeGreaterThanOrEqual(65000.00);
    });

    test('query applies maximum salary filter', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 45000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 65000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 85000.00,
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['salary_max' => 60000]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1);
        expect((float) $results->first()->currentEmployment->salary)->toBe(45000.00);
    });

    test('query applies both min and max salary range filters', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 50000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 75000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 100000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 60000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 80000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 100000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 60000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 80000.00,
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 100000.00,
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport([
            'salary_min' => 55000,
            'salary_max' => 90000,
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $salaries = $results->map(fn ($e) => (float) $e->currentEmployment->salary)->sort()->values();
        expect($salaries[0])->toBe(60000.00)
            ->and($salaries[1])->toBe(80000.00);
    });

    test('query applies hire date from filter', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-15',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-06-01',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-12-20',
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['hire_date_from' => '2023-03-01']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $dates = $results->map(fn ($e) => $e->currentEmployment->hire_date)->sort()->values();
        expect($dates[0]->format('Y-m-d'))->toBe('2023-06-01')
            ->and($dates[1]->format('Y-m-d'))->toBe('2023-12-20');
    });

    test('query applies hire date to filter', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-15',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-06-01',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-12-20',
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport(['hire_date_to' => '2023-09-01']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2);
        $dates = $results->map(fn ($e) => $e->currentEmployment->hire_date)->sort()->values();
        expect($dates[0]->format('Y-m-d'))->toBe('2023-01-15')
            ->and($dates[1]->format('Y-m-d'))->toBe('2023-06-01');
    });

    test('query applies hire date range filters', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-06-01',
                    'is_current' => true, ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-12-01',
                    'is_current' => true, ]);
            })
            ->create();

        $export = new EmployeeExport([
            'hire_date_from' => '2023-03-01',
            'hire_date_to' => '2023-09-01',
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->currentEmployment->hire_date->format('Y-m-d'))->toBe('2023-06-01');
    });

    test('query applies ascending sort by name', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Zoe Employee']);
        Employee::factory()
            ->afterCreating(function ($e) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Alice Employee']);
        Employee::factory()
            ->afterCreating(function ($e) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Bob Employee']);

        $export = new EmployeeExport(['sort_column' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alice Employee')
            ->and($results[1]->name)->toBe('Bob Employee')
            ->and($results[2]->name)->toBe('Zoe Employee');
    });

    test('query applies descending sort by salary', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 40000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'Low Salary']);
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 80000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'High Salary']);
        Employee::factory()
            ->afterCreating(function ($e) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => '2023-01-01',
                    'salary' => 60000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'Medium Salary']);

        $export = new EmployeeExport(['sort_column' => 'salary', 'sort_direction' => 'desc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('High Salary')
            ->and($results[1]->name)->toBe('Medium Salary')
            ->and($results[2]->name)->toBe('Low Salary');
    });

    test('query does not allow invalid sort columns', function () {
        Employee::factory()
            ->afterCreating(function ($e) {
                Employment::factory()->create([
                    'employee_id' => $e->id, 'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(), 'is_current' => true,
                ]);
            })
            ->create(['name' => 'Test Employee']);

        $export = new EmployeeExport(['sort_column' => 'invalid_column']);

        $query = $export->query();

        // Should not throw error, just ignore invalid sort
        expect($query)->toBeInstanceOf(Builder::class);
        expect($query->get())->toHaveCount(1);
    });

    test('query combines multiple filters correctly', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $seniorDev = Position::factory()->create(['name' => 'Senior Developer']);
        $marketingManager = Position::factory()->create(['name' => 'Marketing Manager']);
        $developer = Position::factory()->create(['name' => 'Developer']);

        Employee::factory()
            ->afterCreating(function ($e) use ($engineering, $seniorDev) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $engineering->id,
                    'position_id' => $seniorDev->id,
                    'salary' => 75000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'John Developer']);
        Employee::factory()
            ->afterCreating(function ($e) use ($marketing, $marketingManager) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $marketing->id,
                    'position_id' => $marketingManager->id,
                    'salary' => 65000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'Jane Manager']);
        Employee::factory()
            ->afterCreating(function ($e) use ($engineering, $developer) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $engineering->id,
                    'position_id' => $developer->id,
                    'salary' => 55000.00,
                    'is_current' => true, ]);
            })
            ->create(['name' => 'Bob Developer']);

        $export = new EmployeeExport([
            'department_id' => $engineering->id,
            'salary_min' => 60000,
            'sort_column' => 'name',
            'sort_direction' => 'asc',
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
            'NIK',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Position',
            'Branch',
            'Salary',
            'Status',
            'Hire Date',
            'Created At',
        ]);
    });

    test('map transforms employee data correctly with all fields', function () {
        $department = Department::factory()->create(['name' => 'Engineering']);
        $position = Position::factory()->create(['name' => 'Senior Developer']);

        $employee = Employee::factory()
            ->afterCreating(function ($e) use ($department, $position) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $department->id,
                    'position_id' => $position->id,
                    'salary' => 85000.50,
                    'hire_date' => '2023-03-15',
                    'is_current' => true, ]);
            })
            ->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '555-1234',
                'created_at' => '2023-01-10 14:30:00',
            ]);

        // Refresh to load relationships
        $employee->refresh()->load('currentEmployment.department', 'currentEmployment.position', 'currentEmployment.branch');

        $export = new EmployeeExport([]);
        $mapped = $export->map($employee);

        expect($mapped)->toBe([
            $employee->id,
            $employee->employee_id,
            'John Doe',
            'john@example.com',
            '555-1234',
            'Engineering',
            'Senior Developer',
            $employee->currentEmployment->branch->name ?? null,
            '85000.50',
            $employee->currentEmployment->employment_status,
            '2023-03-15',
            '2023-01-10T14:30:00+00:00',
        ]);
    });

    test('map handles null phone field', function () {
        $department = Department::factory()->create(['name' => 'Marketing']);
        $position = Position::factory()->create(['name' => 'Manager']);

        $employee = Employee::factory()
            ->afterCreating(function ($e) use ($department, $position) {
                $e->employments()->delete();
                Employment::factory()->create(['employee_id' => $e->id, 'department_id' => $department->id,
                    'position_id' => $position->id,
                    'salary' => 70000.00,
                    'hire_date' => '2023-02-01',
                    'is_current' => true, ]);
            })
            ->create([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => null,
            ]);

        // Refresh to load relationships
        $employee->refresh()->load('currentEmployment');

        $export = new EmployeeExport([]);
        $mapped = $export->map($employee);

        expect($mapped[4])->toBeNull(); // Phone field should be null
    });

    test('handles empty filters gracefully', function () {
        Employee::factory()
            ->count(5)
            ->afterCreating(function ($e) {
                Employment::factory()->create([
                    'employee_id' => $e->id,
                    'department_id' => Department::factory()->create()->id,
                    'position_id' => Position::factory()->create()->id,
                    'company_id' => Company::factory()->create()->id,
                    'hire_date' => now()->subYear(),
                    'is_current' => true,
                ]);
            })
            ->create();

        $export = new EmployeeExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(5);
    });

});
