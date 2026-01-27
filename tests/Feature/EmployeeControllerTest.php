<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('employees');

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithPermissions(array $permissionNames = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    if (!empty($permissionNames)) {
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => ucwords(str_replace('.', ' ', $name))]
            )->id;
        }
        $employee->permissions()->sync($permissions);
    }

    return $user;
}

describe('Employee API Endpoints', function () {
    beforeEach(function () {
        // Create user with all employee permissions for existing tests
        $user = createUserWithPermissions(['employee', 'employee.create', 'employee.edit', 'employee.delete']);
        actingAs($user);
    });

    test('index returns paginated employees with proper meta structure', function () {
        Employee::factory()->count(25)->create();

        $response = getJson('/api/employees?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'department' => ['id', 'name'],
                        'position' => ['id', 'name'],
                        'branch' => ['id', 'name'],
                        'salary',
                        'hire_date',
                        'created_at',
                        'updated_at',
                    ]
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

        // Note: +1 because beforeEach creates an employee for the logged-in user
        expect($response->json('meta.total'))->toBe(26)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by multiple fields', function () {
        Employee::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        Employee::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        Employee::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = getJson('/api/employees?search=john');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(2); // John Smith and Bob Johnson (john in email)
    });

    test('index supports advanced filtering by department', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $sales = Department::factory()->create(['name' => 'Sales']);

        Employee::factory()->create(['department_id' => $engineering->id]);
        Employee::factory()->create(['department_id' => $marketing->id]);
        Employee::factory()->create(['department_id' => $sales->id]);

        $response = getJson('/api/employees?department_id=' . $engineering->id);

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['department']['id'])->toBe($engineering->id);
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
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $response = postJson('/api/employees', $employeeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'branch',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'department' => [
                    'id' => $department->id,
                    'name' => $department->name,
                ],
            ])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.branch.id', $branch->id);

        assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'branch_id' => $branch->id,
        ]);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/employees', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'department_id',
                'position_id',
                'branch_id',
                'salary',
                'hire_date'
            ]);
    });

    test('store validates unique email constraint', function () {
        Employee::factory()->create(['email' => 'existing@example.com']);
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = postJson('/api/employees', [
            'name' => 'New Employee',
            'email' => 'existing@example.com',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'salary' => '50000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('show returns single employee with full resource structure', function () {
        $employee = Employee::factory()->create();

        $response = getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'branch',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email
            ]);
    });

    test('show returns 404 for non-existent employee', function () {
        $response = getJson('/api/employees/99999');

        $response->assertNotFound();
    });

    test('update modifies employee and returns updated resource', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['name' => 'Junior Developer']);
        $branch = \App\Models\Branch::factory()->create();
        $employee = Employee::factory()->create([
            'name' => 'Old Name',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
        ]);

        $newPosition = Position::factory()->create(['name' => 'Senior Developer']);
        $newBranch = \App\Models\Branch::factory()->create(['name' => 'New Branch']);

        $updateData = [
            'name' => 'Updated Name',
            'position_id' => $newPosition->id,
            'branch_id' => $newBranch->id,
        ];

        $response = putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'department',
                    'position',
                    'branch',
                    'salary',
                    'hire_date',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'position' => [
                    'id' => $newPosition->id,
                    'name' => $newPosition->name,
                ],
                'branch' => [
                    'id' => $newBranch->id,
                    'name' => $newBranch->name,
                ],
            ]);

        $employee->refresh();
        expect($employee->name)->toBe('Updated Name')
            ->and($employee->position_id)->toBe($newPosition->id)
            ->and($employee->branch_id)->toBe($newBranch->id);
    });

    test('update validates fields when provided with invalid data', function () {
        $employee = Employee::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email format
            'department_id' => 'invalid-dept', // Invalid department
            'branch_id' => 'invalid-branch', // Invalid branch
            'salary' => '-100', // Negative salary
            'hire_date' => 'invalid-date', // Invalid date
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'department_id',
                'branch_id',
                'salary',
                'hire_date'
            ]);
    });

    test('update ignores unique email validation for same employee', function () {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => 'Updated Name',
            'email' => 'john@example.com', // Same email should be allowed
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'salary' => '60000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertOk();
    });

    test('update returns 404 for non-existent employee', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = putJson('/api/employees/99999', [
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'salary' => '50000.00',
            'hire_date' => '2023-01-01',
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
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('employees_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/employees_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies filters correctly', function () {
        $engineering = Department::factory()->create(['name' => 'Engineering']);
        $marketing = Department::factory()->create(['name' => 'Marketing']);
        $developer = Position::factory()->create(['name' => 'Developer']);
        $manager = Position::factory()->create(['name' => 'Manager']);
        $branchA = \App\Models\Branch::factory()->create(['name' => 'Branch A']);
        $branchB = \App\Models\Branch::factory()->create(['name' => 'Branch B']);

        Employee::factory()->create(['department_id' => $engineering->id, 'position_id' => $developer->id, 'branch_id' => $branchA->id]);
        Employee::factory()->create(['department_id' => $marketing->id, 'position_id' => $manager->id, 'branch_id' => $branchB->id]);
        Employee::factory()->create(['position_id' => $manager->id, 'branch_id' => $branchA->id]);

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

describe('Employee API Permission Tests', function () {
    test('store returns 403 when user lacks employee.create permission', function () {
        $user = createUserWithPermissions(['employee']);
        actingAs($user);

        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = postJson('/api/employees', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'salary' => '50000.00',
            'hire_date' => '2023-01-01',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks employee.edit permission', function () {
        $user = createUserWithPermissions(['employee']);
        actingAs($user);

        $employee = Employee::factory()->create();

        $response = putJson("/api/employees/{$employee->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks employee.delete permission', function () {
        $user = createUserWithPermissions(['employee']);
        actingAs($user);

        $employee = Employee::factory()->create();

        $response = deleteJson("/api/employees/{$employee->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});
