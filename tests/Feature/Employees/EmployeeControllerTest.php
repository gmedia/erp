<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('employees');

describe('Employee API Endpoints', function () {
    beforeEach(function () {
        // Create user with all employee permissions for existing tests
        $user = createTestUserWithPermissions(['employee', 'employee.create', 'employee.edit', 'employee.delete']);
        Sanctum::actingAs($user, ['*']);
    });

    test('index returns paginated employees with proper meta structure', function () {
        $baseline = Employee::count();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();
        $company = Company::factory()->create();

        // Give the beforeEach user's employee a currentEmployment so it doesn't break structure assertions
        Employment::factory()->create([
            'employee_id' => auth()->user()->employee->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'is_current' => true,
        ]);

        $employees = Employee::factory()->count(25)->create();

        // Create Employment records for all employees so current_employment is not null
        foreach ($employees as $employee) {
            Employment::factory()->create([
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'is_current' => true,
            ]);
        }

        $response = getJson('/api/employees?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'current_employment' => [
                            'id',
                            'department_id',
                            'position_id',
                            'branch_id',
                            'salary',
                            'hire_date',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBe($baseline + 25)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by multiple fields', function () {
        Employee::query()->delete();
        $user = createTestUserWithPermissions(['employee', 'employee.create', 'employee.edit', 'employee.delete']);
        $user->employee->update([
            'name' => 'Zebra Tester',
            'email' => 'zebra@example.com',
        ]);
        Sanctum::actingAs($user, ['*']);

        Employee::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        Employee::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        Employee::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = getJson('/api/employees?search=john');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2); // John Smith and Bob Johnson (john in name/email)
    });

    test('index supports advanced filtering by department', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $sales = Department::factory()->create(['name' => 'Sales']);

        $company = Company::factory()->create();

        Employee::factory()->afterCreating(function (Employee $e) use ($engineering, $company) {
            $e->employments()->delete();
            $e->currentEmployment()->create([
                'company_id' => $company->id,
                'department_id' => $engineering->id,
                'position_id' => Position::factory()->create()->id,
                'hire_date' => now()->subYear(),
                'employment_status' => 'regular',
            ]);
        })->create();
        Employee::factory()->afterCreating(function (Employee $e) use ($marketing) {
            $e->employments()->delete();
            $e->currentEmployment()->create([
                'company_id' => Company::factory()->create()->id,
                'department_id' => $marketing->id,
                'position_id' => Position::factory()->create()->id,
                'hire_date' => now()->subYear(),
                'employment_status' => 'regular',
            ]);
        })->create();
        Employee::factory()->afterCreating(function (Employee $e) use ($sales) {
            $e->employments()->delete();
            $e->currentEmployment()->create([
                'company_id' => Company::factory()->create()->id,
                'department_id' => $sales->id,
                'position_id' => Position::factory()->create()->id,
                'hire_date' => now()->subYear(),
                'employment_status' => 'regular',
            ]);
        })->create();

        $response = getJson('/api/employees?department_id=' . $engineering->id . '&company_id=' . $company->id);

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['current_employment']['department']['id'])->toBe($engineering->id);
    });

    test('index supports sorting by different fields', function () {
        Employee::factory()->create(['name' => 'Z Employee']);
        Employee::factory()->create(['name' => 'A Employee']);

        $response = getJson('/api/employees?sort_by=name&sort_direction=asc');

        $response->assertOk();

        // Note: beforeEach creates an employee with random name, so we only check that our sorted employees appear
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Employee', $names);
        $zIndex = array_search('Z Employee', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates employee with valid data and returns 201 status', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();

        $employeeData = [
            'employee_id' => 'EMP-001',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'current_employment' => [
                'company_id' => $company->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $response = postJson('/api/employees', $employeeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'current_employment' => [
                        'id',
                        'department_id',
                        'position_id',
                        'branch_id',
                        'salary',
                        'hire_date',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.current_employment.branch_id', $branch->id);

        assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        assertDatabaseHas('employments', [
            'employee_id' => $response->json('data.id'),
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'is_current' => true,
        ]);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/employees', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'employee_id',
                'name',
                'email',
                'current_employment.department_id',
                'current_employment.position_id',
                'current_employment.branch_id',
                'current_employment.hire_date',
                'current_employment.employment_status',
            ]);
    });

    test('store validates unique email constraint', function () {
        Employee::factory()->create(['email' => 'existing@example.com']);
        $company = Company::factory()->create();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();

        $response = postJson('/api/employees', [
            'employee_id' => 'EMP-002',
            'name' => 'New Employee',
            'email' => 'existing@example.com',
            'current_employment' => [
                'company_id' => $company->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'salary' => '50000.00',
                'hire_date' => '2023-01-01',
                'employment_status' => 'regular',
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('show returns single employee with full resource structure', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();
        $company = Company::factory()->create();
        $employee = Employee::factory()->create();

        Employment::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'is_current' => true,
        ]);

        $employee->load('currentEmployment.department', 'currentEmployment.position', 'currentEmployment.branch');

        $response = getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'current_employment' => [
                        'id',
                        'department',
                        'position',
                        'branch',
                        'salary',
                        'hire_date',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
            ]);
    });

    test('show returns 404 for non-existent employee', function () {
        $response = getJson('/api/employees/99999');

        $response->assertNotFound();
    });

    test('update modifies employee and returns updated resource', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['name' => 'Junior Developer']);
        $branch = Branch::factory()->create();
        $employee = Employee::factory()->create([
            'name' => 'Old Name',
        ]);
        $employee->load('currentEmployment.department', 'currentEmployment.position', 'currentEmployment.branch');

        $newPosition = Position::factory()->create(['name' => 'Senior Developer']);
        $newBranch = Branch::factory()->create(['name' => 'New Branch']);

        $updateData = [
            'name' => 'Updated Name',
            'current_employment' => [
                'position_id' => $newPosition->id,
                'branch_id' => $newBranch->id,
                'company_id' => Company::factory()->create()->id,
                'hire_date' => '2023-01-01',
            ],
        ];

        $response = putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'current_employment' => [
                        'id',
                        'department_id',
                        'position_id',
                        'branch_id',
                        'salary',
                        'hire_date',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'position_id' => $newPosition->id,
                'branch_id' => $newBranch->id,
            ]);

        $employee->refresh();
        expect($employee->name)->toBe('Updated Name');

        assertDatabaseHas('employments', [
            'employee_id' => $employee->id,
            'position_id' => $newPosition->id,
            'branch_id' => $newBranch->id,
            'is_current' => true,
        ]);
    });

    test('update validates fields when provided with invalid data', function () {
        $employee = Employee::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'employee_id' => '', // Empty ID
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email format
            'current_employment' => [
                'department_id' => 'invalid-dept', // Invalid department
                'branch_id' => 'invalid-branch', // Invalid branch
                'salary' => '-100', // Negative salary
                'hire_date' => 'invalid-date', // Invalid date
                'employment_status' => 'invalid-status', // Invalid enum
                'termination_date' => 'invalid-date', // Invalid date
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'employee_id',
                'name',
                'email',
                'current_employment.department_id',
                'current_employment.branch_id',
                'current_employment.salary',
                'current_employment.hire_date',
                'current_employment.employment_status',
                'current_employment.termination_date',
            ]);
    });

    test('update ignores unique email validation for same employee', function () {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();
        $company = Company::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => 'Updated Name',
            'email' => 'john@example.com', // Same email should be allowed
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'company_id' => $company->id,
                'hire_date' => '2023-01-01',
                'employment_status' => 'regular',
            ],
        ]);

        $response->assertOk();
    });

    test('update returns 404 for non-existent employee', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();

        $response = putJson('/api/employees/99999', [
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'hire_date' => '2023-01-01',
                'employment_status' => 'regular',
            ],
        ]);

        $response->assertNotFound();
    });

    test('destroy removes employee and returns 204 status', function () {
        $employee = Employee::factory()->create();

        $response = deleteJson("/api/employees/{$employee->id}");

        $response->assertNoContent();

        assertDatabaseMissing('employees', ['id' => $employee->id]);
    });

    test('destroy returns 404 for non-existent employee', function () {
        $response = deleteJson('/api/employees/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Employee::factory()->count(5)->create();

        $response = postJson('/api/employees/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString();
        expect($data['filename'])->toContain('employees_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/employees_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies filters correctly', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $developer = Position::factory()->create(['name' => 'Developer']);
        $manager = Position::factory()->create(['name' => 'Manager']);
        $branchA = Branch::factory()->create(['name' => 'Branch A']);
        $branchB = Branch::factory()->create(['name' => 'Branch B']);
        $company = Company::factory()->create();

        Employee::factory()
            ->afterCreating(function ($employee) use ($engineering, $developer, $branchA, $company) {
                $employee->employments()->delete();
                $employee->employments()->create([
                    'company_id' => $company->id,
                    'department_id' => $engineering->id,
                    'position_id' => $developer->id,
                    'branch_id' => $branchA->id,
                    'is_current' => true,
                ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($employee) use ($marketing, $manager, $branchB, $company) {
                $employee->employments()->delete();
                $employee->employments()->create([
                    'company_id' => $company->id,
                    'department_id' => $marketing->id,
                    'position_id' => $manager->id,
                    'branch_id' => $branchB->id,
                    'is_current' => true,
                ]);
            })
            ->create();
        Employee::factory()
            ->afterCreating(function ($employee) use ($manager, $branchA, $company) {
                $employee->employments()->delete();
                $employee->employments()->create([
                    'company_id' => $company->id,
                    'position_id' => $manager->id,
                    'branch_id' => $branchA->id,
                    'is_current' => true,
                ]);
            })
            ->create();

        $response = postJson('/api/employees/export', [
            'department_id' => $engineering->id,
            'position_id' => $developer->id,
            'branch_id' => $branchA->id,
        ]);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });

});
