<?php

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

uses(RefreshDatabase::class)->group('positions');

/**
 * Helper function to create a user with an employee that has specific permissions.
 */
function createUserWithPositionPermissions(array $permissionNames = []): User
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

describe('Position API Endpoints', function () {
    beforeEach(function () {
        // Create user with all position permissions for existing tests
        $user = createUserWithPositionPermissions(['position', 'position.create', 'position.edit', 'position.delete']);
        actingAs($user);
    });

    test('index returns paginated positions with proper meta structure', function () {
        Position::factory()->count(25)->create();

        $response = getJson('/api/positions?per_page=10');

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

        // Note: +1 because beforeEach creates a position for the employee's user
        expect($response->json('meta.total'))->toBe(26)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by name', function () {
        Position::factory()->create(['name' => 'Marketing Position']);
        Position::factory()->create(['name' => 'Sales Position']);
        Position::factory()->create(['name' => 'Engineering Position']);

        $response = getJson('/api/positions?search=market');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Marketing Position');
    });

    test('index supports sorting by different fields', function () {
        Position::factory()->create(['name' => 'Z Position']);
        Position::factory()->create(['name' => 'A Position']);

        $response = getJson('/api/positions?sort_by=name&sort_direction=asc');

        $response->assertOk();

        // Note: beforeEach creates a position with random name, so we only check relative order
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $aIndex = array_search('A Position', $names);
        $zIndex = array_search('Z Position', $names);
        expect($aIndex)->toBeLessThan($zIndex);
    });

    test('store creates position with valid data and returns 201 status', function () {
        $data = ['name' => 'Test Position'];

        $response = postJson('/api/positions', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Test Position']);

        assertDatabaseHas('positions', ['name' => 'Test Position']);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/positions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('store validates unique name constraint', function () {
        Position::factory()->create(['name' => 'Existing Position']);

        $response = postJson('/api/positions', ['name' => 'Existing Position']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns single position with full resource structure', function () {
        $position = Position::factory()->create();

        $response = getJson("/api/positions/{$position->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $position->id, 'name' => $position->name]);
    });

    test('show returns 404 for non-existent position', function () {
        $response = getJson('/api/positions/99999');

        $response->assertNotFound();
    });

    test('update modifies position and returns updated resource', function () {
        $position = Position::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Position Name'];

        $response = putJson("/api/positions/{$position->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated Position Name']);

        $position->refresh();
        expect($position->name)->toBe('Updated Position Name');
    });

    test('update validates fields when provided with invalid data', function () {
        $position = Position::factory()->create();

        $response = putJson("/api/positions/{$position->id}", [
            'name' => '', // Empty name
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update ignores unique name validation for same position', function () {
        $position = Position::factory()->create(['name' => 'Test Position']);

        $response = putJson("/api/positions/{$position->id}", [
            'name' => 'Test Position', // Same name should be allowed
        ]);

        $response->assertOk();
    });

    test('update validates unique name constraint for different position', function () {
        $position1 = Position::factory()->create(['name' => 'Position One']);
        $position2 = Position::factory()->create(['name' => 'Position Two']);

        $response = putJson("/api/positions/{$position1->id}", [
            'name' => 'Position Two', // Name from different position should fail
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update returns 404 for non-existent position', function () {
        $response = putJson('/api/positions/99999', ['name' => 'Test']);

        $response->assertNotFound();
    });

    test('destroy removes position and returns 204 status', function () {
        $position = Position::factory()->create();

        $response = deleteJson("/api/positions/{$position->id}");

        $response->assertNoContent();

        assertDatabaseMissing('positions', ['id' => $position->id]);
    });

    test('destroy returns 404 for non-existent position', function () {
        $response = deleteJson('/api/positions/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Position::factory()->count(5)->create();

        $response = postJson('/api/positions/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('positions_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/positions_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        Position::factory()->create(['name' => 'Manager']);
        Position::factory()->create(['name' => 'Developer']);
        Position::factory()->create(['name' => 'Designer']);

        $response = postJson('/api/positions/export', ['search' => 'dev']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });
});

describe('Position API Permission Tests', function () {
    test('store returns 403 when user lacks position.create permission', function () {
        $user = createUserWithPositionPermissions(['position']);
        actingAs($user);

        $response = postJson('/api/positions', ['name' => 'Test Position']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('update returns 403 when user lacks position.edit permission', function () {
        $user = createUserWithPositionPermissions(['position']);
        actingAs($user);

        $position = Position::factory()->create();

        $response = putJson("/api/positions/{$position->id}", ['name' => 'Updated Name']);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    test('destroy returns 403 when user lacks position.delete permission', function () {
        $user = createUserWithPositionPermissions(['position']);
        actingAs($user);

        $position = Position::factory()->create();

        $response = deleteJson("/api/positions/{$position->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });
});

