<?php

use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('employees');

test('employee permission and user management is forbidden without user permission', function () {
    $employee = Employee::factory()->create();
    Sanctum::actingAs(createTestUserWithPermissions([]), ['*']);

    getJson("/api/employees/{$employee->id}/permissions")->assertForbidden();
    postJson("/api/employees/{$employee->id}/permissions", [])->assertForbidden();
    getJson("/api/employees/{$employee->id}/user")->assertForbidden();
    postJson("/api/employees/{$employee->id}/user", [])->assertForbidden();
});

test('view-only user permission cannot change employee permissions or user', function () {
    $employee = Employee::factory()->create();
    Sanctum::actingAs(createTestUserWithPermissions(['user']), ['*']);

    getJson("/api/employees/{$employee->id}/permissions")->assertOk();
    getJson("/api/employees/{$employee->id}/user")->assertOk();
    postJson("/api/employees/{$employee->id}/permissions", [])->assertForbidden();
    postJson("/api/employees/{$employee->id}/user", [])->assertForbidden();
});

test('grant command gives user.edit to one employee by email and is idempotent', function () {
    $employee = Employee::factory()->create(['email' => 'pengelola@example.com']);
    Permission::firstOrCreate(['name' => 'user.edit'], ['display_name' => 'Edit User']);

    $this->artisan('permissions:grant-user-edit', ['email' => 'pengelola@example.com'])->assertSuccessful();
    $this->artisan('permissions:grant-user-edit', ['email' => 'pengelola@example.com'])->assertSuccessful();

    expect($employee->permissions()->where('name', 'user.edit')->count())->toBe(1);
});

test('user edit permission passes the authorization gate for changes', function () {
    $employee = Employee::factory()->create();
    Sanctum::actingAs(createTestUserWithPermissions(['user', 'user.edit']), ['*']);

    $permissionsResponse = postJson("/api/employees/{$employee->id}/permissions", []);
    $userResponse = postJson("/api/employees/{$employee->id}/user", []);

    expect($permissionsResponse->status())->not->toBe(403);
    expect($userResponse->status())->not->toBe(403);
});
