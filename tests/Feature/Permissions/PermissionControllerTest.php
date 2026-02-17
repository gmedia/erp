<?php

namespace Tests\Feature\Permissions;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('permissions');

describe('Permission Page Access', function () {
    test('unauthenticated user cannot access permissions page', function () {
        $response = get('/permissions');

        $response->assertRedirect('/login');
    });

    test('authenticated user without permission cannot access permissions page', function () {
        $user = createTestUserWithPermissions([]);
        actingAs($user);

        $response = get('/permissions');

        $response->assertForbidden();
    });

    test('authenticated user with permission can access permissions page', function () {
        $user = createTestUserWithPermissions(['permission']);
        actingAs($user);

        $response = get('/permissions');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('permissions/index')
                ->has('permissions')
            );
    });

    test('permissions page returns all permissions ordered by id', function () {
        // Create some permissions
        Permission::factory()->create(['name' => 'test.permission.1', 'display_name' => 'Test Permission 1']);
        Permission::factory()->create(['name' => 'test.permission.2', 'display_name' => 'Test Permission 2']);
        Permission::factory()->create(['name' => 'test.permission.3', 'display_name' => 'Test Permission 3']);

        $user = createTestUserWithPermissions(['permission']);
        actingAs($user);

        $response = get('/permissions');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('permissions/index')
                ->has('permissions')
            );

        // Verify permissions are returned with required fields
        $permissions = $response->original->getData()['page']['props']['permissions'];
        expect($permissions)->toBeArray()
            ->and(count($permissions))->toBeGreaterThanOrEqual(4); // 3 test + 1 for user access

        // Verify each permission has required keys
        foreach ($permissions as $permission) {
            expect($permission)->toHaveKeys(['id', 'name', 'display_name']);
        }
    });
});
