<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('departments');

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithDepartmentPermissions(array $permissionNames = []): User
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

describe('Department API Endpoints', function () {
    beforeEach(function () {
        // Clear departments to ensure isolation from other tests' side effects
        Department::query()->delete();

        // Create user with all department permissions for existing tests
        $user = createUserWithDepartmentPermissions(['department', 'department.create', 'department.edit', 'department.delete']);
        actingAs($user);
    });

    test('index returns paginated departments with proper meta structure', function () {
        $baseline = Department::count();
        Department::factory()->count(25)->create();

        $response = getJson('/api/departments?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
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

        // Total should be baseline + 25
        expect($response->json('meta.total'))->toBe($baseline + 25)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by name', function () {
        Department::factory()->create(['name' => 'Marketing Department']);
        Department::factory()->create(['name' => 'Sales Department']);
        Department::factory()->create(['name' => 'Engineering Department']);

        $response = getJson('/api/departments?search=market');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Marketing Department');
    });

    test('index supports sorting by different fields', function () {
        Department::factory()->create(['name' => 'Z Department']);
        Department::factory()->create(['name' => 'A Department']);

        $response = getJson('/api/departments?sort_by=name&sort_direction=asc');

        $response->assertOk();

        // Note: beforeEach creates a department with random name, so we only check relative order
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Department', $names);
        $zIndex = array_search('Z Department', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates department with valid data and returns 201 status', function () {
        $data = ['name' => 'Test Department'];

        $response = postJson('/api/departments', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Test Department']);

        assertDatabaseHas('departments', ['name' => 'Test Department']);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/departments', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('store validates unique name constraint', function () {
        Department::factory()->create(['name' => 'Existing Department']);

        $response = postJson('/api/departments', ['name' => 'Existing Department']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns single department with full resource structure', function () {
        $department = Department::factory()->create();

        $response = getJson("/api/departments/{$department->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $department->id, 'name' => $department->name]);
    });

    test('show returns 404 for non-existent department', function () {
        $response = getJson('/api/departments/99999');

        $response->assertNotFound();
    });

    test('update modifies department and returns updated resource', function () {
        $department = Department::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Department Name'];

        $response = putJson("/api/departments/{$department->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated Department Name']);

        $department->refresh();
        expect($department->name)->toBe('Updated Department Name');
    });

    test('update validates fields when provided with invalid data', function () {
        $department = Department::factory()->create();

        $response = putJson("/api/departments/{$department->id}", [
            'name' => '', // Empty name
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update ignores unique name validation for same department', function () {
        $department = Department::factory()->create(['name' => 'Test Department']);

        $response = putJson("/api/departments/{$department->id}", [
            'name' => 'Test Department', // Same name should be allowed
        ]);

        $response->assertOk();
    });

    test('update validates unique name constraint for different department', function () {
        $department1 = Department::factory()->create(['name' => 'Department One']);
        $department2 = Department::factory()->create(['name' => 'Department Two']);

        $response = putJson("/api/departments/{$department1->id}", [
            'name' => 'Department Two', // Name from different department should fail
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update returns 404 for non-existent department', function () {
        $response = putJson('/api/departments/99999', ['name' => 'Test']);

        $response->assertNotFound();
    });

    test('destroy removes department and returns 204 status', function () {
        $department = Department::factory()->create();

        $response = deleteJson("/api/departments/{$department->id}");

        $response->assertNoContent();

        assertDatabaseMissing('departments', ['id' => $department->id]);
    });

    test('destroy returns 404 for non-existent department', function () {
        $response = deleteJson('/api/departments/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Department::factory()->count(5)->create();

        $response = postJson('/api/departments/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('departments_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/departments_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        Department::factory()->create(['name' => 'Manager']);
        Department::factory()->create(['name' => 'Developer']);
        Department::factory()->create(['name' => 'Designer']);

        $response = postJson('/api/departments/export', ['search' => 'dev']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });
});

describe('Department API Permission Tests', function () {
    test('store returns 403 when user lacks department.create permission', function () {
        $user = createUserWithDepartmentPermissions(['department']);
        actingAs($user);

        $response = postJson('/api/departments', ['name' => 'Test Department']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks department.edit permission', function () {
        $user = createUserWithDepartmentPermissions(['department']);
        actingAs($user);

        $department = Department::factory()->create();

        $response = putJson("/api/departments/{$department->id}", ['name' => 'Updated Name']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks department.delete permission', function () {
        $user = createUserWithDepartmentPermissions(['department']);
        actingAs($user);

        $department = Department::factory()->create();

        $response = deleteJson("/api/departments/{$department->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});

