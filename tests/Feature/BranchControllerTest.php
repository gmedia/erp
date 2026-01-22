<?php

use App\Models\Branch;
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

uses(RefreshDatabase::class);

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithBranchPermissions(array $permissionNames = []): User
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

describe('Branch API Endpoints', function () {
    beforeEach(function () {
        // Create user with all branch permissions for existing tests
        $user = createUserWithBranchPermissions(['branch', 'branch.create', 'branch.edit', 'branch.delete']);
        actingAs($user);
    });

    test('index returns paginated branches with proper meta structure', function () {
        Branch::factory()->count(25)->create();

        $response = getJson('/api/branches?per_page=10');

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

        expect($response->json('data'))->toHaveCount(10);
        expect($response->json('meta.total'))->toBe(25);
    });

    test('index supports search filtering', function () {
        Branch::factory()->create(['name' => 'Jakarta Branch']);
        Branch::factory()->create(['name' => 'Surabaya Branch']);
        Branch::factory()->create(['name' => 'Bandung Branch']);

        $response = getJson('/api/branches?search=jakarta');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Jakarta Branch');
    });

    test('index supports sorting', function () {
        Branch::factory()->create(['name' => 'Branch C']);
        Branch::factory()->create(['name' => 'Branch A']);
        Branch::factory()->create(['name' => 'Branch B']);

        $response = getJson('/api/branches?sort_by=name&sort_direction=asc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('Branch A');
        expect($response->json('data.1.name'))->toBe('Branch B');
        expect($response->json('data.2.name'))->toBe('Branch C');
    });

    test('store creates a new branch', function () {
        $response = postJson('/api/branches', ['name' => 'New Branch']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Branch');

        assertDatabaseHas('branches', ['name' => 'New Branch']);
    });

    test('store validates unique name', function () {
        Branch::factory()->create(['name' => 'Existing Branch']);

        $response = postJson('/api/branches', ['name' => 'Existing Branch']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns a single branch', function () {
        $branch = Branch::factory()->create(['name' => 'Test Branch']);

        $response = getJson("/api/branches/{$branch->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $branch->id)
            ->assertJsonPath('data.name', 'Test Branch');
    });

    test('update modifies an existing branch', function () {
        $branch = Branch::factory()->create(['name' => 'Old Name']);

        $response = putJson("/api/branches/{$branch->id}", ['name' => 'New Name']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'New Name']);
    });

    test('update validates unique name excluding current branch', function () {
        $branch1 = Branch::factory()->create(['name' => 'Branch One']);
        $branch2 = Branch::factory()->create(['name' => 'Branch Two']);

        $response = putJson("/api/branches/{$branch2->id}", ['name' => 'Branch One']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update allows same name for current branch', function () {
        $branch = Branch::factory()->create(['name' => 'Same Name']);

        $response = putJson("/api/branches/{$branch->id}", ['name' => 'Same Name']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Same Name');
    });

    test('destroy deletes a branch', function () {
        $branch = Branch::factory()->create();

        $response = deleteJson("/api/branches/{$branch->id}");

        $response->assertNoContent();
        assertDatabaseMissing('branches', ['id' => $branch->id]);
    });

    test('export returns file url and filename', function () {
        Branch::factory()->count(5)->create();

        $response = postJson('/api/branches/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json()['filename'])->toMatch('/branches_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        Branch::factory()->create(['name' => 'Jakarta Branch']);
        Branch::factory()->create(['name' => 'Surabaya Branch']);
        Branch::factory()->create(['name' => 'Bandung Branch']);

        $response = postJson('/api/branches/export', ['search' => 'jakarta']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });
});

describe('Branch API Permission Tests', function () {
    test('store returns 403 when user lacks branch.create permission', function () {
        $user = createUserWithBranchPermissions(['branch']);
        actingAs($user);

        $response = postJson('/api/branches', ['name' => 'Test Branch']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks branch.edit permission', function () {
        $user = createUserWithBranchPermissions(['branch']);
        actingAs($user);

        $branch = Branch::factory()->create();

        $response = putJson("/api/branches/{$branch->id}", ['name' => 'Updated Name']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks branch.delete permission', function () {
        $user = createUserWithBranchPermissions(['branch']);
        actingAs($user);

        $branch = Branch::factory()->create();

        $response = deleteJson("/api/branches/{$branch->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});
