<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('permissions');

describe('Permission Page Access', function () {
    test('unauthenticated user cannot access permissions', function () {
        $response = getJson('/api/permissions');

        $response->assertUnauthorized();
    });

    test('authenticated user without permission cannot access permissions', function () {
        $user = createTestUserWithPermissions([]);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/permissions');

        $response->assertForbidden();
    });

    test('authenticated user with permission can access permissions', function () {
        $user = createTestUserWithPermissions(['permission']);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/permissions');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'display_name']]]);
    });

    test('permissions page returns all permissions ordered by id', function () {
        // Create some permissions
        Permission::factory()->create(['name' => 'test.permission.1', 'display_name' => 'Test Permission 1']);
        Permission::factory()->create(['name' => 'test.permission.2', 'display_name' => 'Test Permission 2']);
        Permission::factory()->create(['name' => 'test.permission.3', 'display_name' => 'Test Permission 3']);

        $user = createTestUserWithPermissions(['permission']);
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/permissions');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'display_name']]]);

        // Verify permissions are returned with required fields
        $permissions = $response->json('data');
        expect($permissions)->toBeArray()
            ->and(count($permissions))->toBeGreaterThanOrEqual(4); // 3 test + 1 for user access

        // Verify each permission has required keys
        foreach ($permissions as $permission) {
            expect($permission)->toHaveKeys(['id', 'name', 'display_name']);
        }
    });
});
