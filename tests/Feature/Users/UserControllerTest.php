<?php

namespace Tests\Feature\Users;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('users');

describe('User Page Access', function () {
    test('unauthenticated user cannot access users page', function () {
        $response = get('/users');

        $response->assertRedirect('/login');
    });

    test('authenticated user without permission cannot access users page', function () {
        $user = createTestUserWithPermissions([]);
        actingAs($user);

        $response = get('/users');

        $response->assertForbidden();
    });

    test('authenticated user with permission can access users page', function () {
        $user = createTestUserWithPermissions(['user']);
        actingAs($user);

        $response = get('/users');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('users/index'));
    });
});

describe('Get User By Employee API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['user']);
        actingAs($user);
    });

    test('returns null user when employee has no linked user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = getJson("/api/employees/{$employee->id}/user");

        $response->assertOk()
            ->assertJson([
                'user' => null,
                'employee' => [
                    'name' => $employee->name,
                    'email' => $employee->email,
                ],
            ]);
    });

    test('returns user data when employee has linked user', function () {
        $linkedUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $linkedUser->id]);

        $response = getJson("/api/employees/{$employee->id}/user");

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $linkedUser->id,
                    'name' => $linkedUser->name,
                    'email' => $linkedUser->email,
                ],
                'employee' => [
                    'name' => $employee->name,
                    'email' => $employee->email,
                ],
            ]);
    });

    test('returns 404 for non-existent employee', function () {
        $response = getJson('/api/employees/99999/user');

        $response->assertNotFound();
    });
});

describe('Update User API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['user']);
        actingAs($user);
    });

    test('creates new user for employee without linked user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User updated successfully.',
                'user' => [
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                ],
            ]);

        assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $employee->refresh();
        expect($employee->user_id)->not->toBeNull();
    });

    test('updates existing user for employee with linked user', function () {
        $linkedUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $employee = Employee::factory()->create(['user_id' => $linkedUser->id]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User updated successfully.',
                'user' => [
                    'id' => $linkedUser->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        $linkedUser->refresh();
        expect($linkedUser->name)->toBe('Updated Name')
            ->and($linkedUser->email)->toBe('updated@example.com');
    });

    test('validates required fields for new user', function () {
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    test('password is optional when updating existing user', function () {
        $linkedUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $linkedUser->id]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'Updated Name',
            'email' => $linkedUser->email,
            // No password provided
        ]);

        $response->assertOk();
    });

    test('validates unique email constraint', function () {
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('allows same email when updating existing user', function () {
        $linkedUser = User::factory()->create(['email' => 'same@example.com']);
        $employee = Employee::factory()->create(['user_id' => $linkedUser->id]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'Updated Name',
            'email' => 'same@example.com', // Same email should be allowed
        ]);

        $response->assertOk();
    });
});

describe('User API Permission Tests', function () {
    test('getUserByEmployee returns 403 when user lacks user permission', function () {
        $user = createTestUserWithPermissions([]);
        actingAs($user);

        $employee = Employee::factory()->create();

        $response = getJson("/api/employees/{$employee->id}/user");

        $response->assertForbidden();
    });

    test('updateUser returns 403 when user lacks user permission', function () {
        $user = createTestUserWithPermissions([]);
        actingAs($user);

        $employee = Employee::factory()->create(['user_id' => null]);

        $response = postJson("/api/employees/{$employee->id}/user", [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    });
});
