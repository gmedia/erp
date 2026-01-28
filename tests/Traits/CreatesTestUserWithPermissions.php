<?php

namespace Tests\Traits;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;

/**
 * Shared trait for creating test users with specific permissions.
 *
 * This trait provides a consistent way to create authenticated users
 * with permissions across all feature tests (Employee, Customer, Supplier, etc.)
 */
trait CreatesTestUserWithPermissions
{
    /**
     * Create a user with an employee that has specific permissions.
     *
     * @param array<string> $permissionNames Array of permission names
     * @return User
     */
    protected function createTestUserWithPermissions(array $permissionNames = []): User
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        if (!empty($permissionNames)) {
            $permissions = [];
            foreach ($permissionNames as $name) {
                $permissions[] = Permission::firstOrCreate(
                    ['name' => $name],
                    ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))]
                )->id;
            }
            $employee->permissions()->sync($permissions);
        }

        return $user;
    }
}
