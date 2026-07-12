<?php

use App\Domain\Employees\EmployeeFilterService;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('employees');

beforeEach(function () {
    Employee::query()->delete();
});

test('applySearch adds where clause for search term', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'John Doe', 'email' => 'unique@example.com']);
    Employee::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    Employee::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $query = Employee::query();
    $service->applySearch($query, 'john', ['name', 'email']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('applyAdvancedFilters applies department filter', function () {
    $service = new EmployeeFilterService;

    $engineering = Department::factory()->create(['name' => 'Engineering']);
    $marketing = Department::factory()->create(['name' => 'Marketing']);
    $sales = Department::factory()->create(['name' => 'Sales']);

    $emp1 = Employee::factory()->create();
    $emp2 = Employee::factory()->create();
    $emp3 = Employee::factory()->create();

    Employment::factory()->create(['employee_id' => $emp1->id, 'department_id' => $engineering->id, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp2->id, 'department_id' => $marketing->id, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp3->id, 'department_id' => $sales->id, 'is_current' => true]);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, ['department_id' => $engineering->id]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($emp1->id);
});

test('applyAdvancedFilters applies position filter', function () {
    $service = new EmployeeFilterService;

    $developer = Position::factory()->create(['name' => 'Developer']);
    $manager = Position::factory()->create(['name' => 'Manager']);

    $emp1 = Employee::factory()->create();
    $emp2 = Employee::factory()->create();

    Employment::factory()->create(['employee_id' => $emp1->id, 'position_id' => $developer->id, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp2->id, 'position_id' => $manager->id, 'is_current' => true]);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, ['position_id' => $developer->id]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($emp1->id);
});

test('applyAdvancedFilters applies salary range filters', function () {
    $service = new EmployeeFilterService;

    $emp1 = Employee::factory()->create();
    $emp2 = Employee::factory()->create();
    $emp3 = Employee::factory()->create();

    Employment::factory()->create(['employee_id' => $emp1->id, 'salary' => 50000.00, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp2->id, 'salary' => 75000.00, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp3->id, 'salary' => 100000.00, 'is_current' => true]);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, [
        'salary_min' => 60000,
        'salary_max' => 90000,
    ]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($emp2->id);
});

test('applyAdvancedFilters applies hire date range filters', function () {
    $service = new EmployeeFilterService;

    $emp1 = Employee::factory()->create();
    $emp2 = Employee::factory()->create();
    $emp3 = Employee::factory()->create();

    Employment::factory()->create(['employee_id' => $emp1->id, 'hire_date' => '2023-01-01', 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp2->id, 'hire_date' => '2023-06-01', 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp3->id, 'hire_date' => '2023-12-01', 'is_current' => true]);

    $query = Employee::query();
    $service->applyAdvancedFilters($query, [
        'hire_date_from' => '2023-03-01',
        'hire_date_to' => '2023-09-01',
    ]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($emp2->id);
});

test('applyAdvancedFilters handles empty filters', function () {
    $service = new EmployeeFilterService;

    $emp1 = Employee::factory()->create();
    $emp2 = Employee::factory()->create();
    $emp3 = Employee::factory()->create();

    Employment::factory()->create(['employee_id' => $emp1->id, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp2->id, 'is_current' => true]);
    Employment::factory()->create(['employee_id' => $emp3->id, 'is_current' => true]);

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
    $service->applySorting(
        $query,
        'name',
        'asc',
        ['id', 'name', 'email', 'created_at', 'updated_at']
    );

    $results = $query->get();

    expect($results->first()->name)->toBe('A Employee')
        ->and($results->last()->name)->toBe('Z Employee');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'A Employee']);
    Employee::factory()->create(['name' => 'Z Employee']);

    $query = Employee::query();
    $service->applySorting(
        $query,
        'name',
        'desc',
        ['id', 'name', 'email', 'created_at', 'updated_at']
    );

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Employee')
        ->and($results->last()->name)->toBe('A Employee');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new EmployeeFilterService;

    Employee::factory()->create(['name' => 'Test Employee']);

    $query = Employee::query();
    $originalSql = $query->toSql();

    $service->applySorting(
        $query,
        'invalid_field',
        'asc',
        ['id', 'name', 'email', 'created_at', 'updated_at']
    );

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
