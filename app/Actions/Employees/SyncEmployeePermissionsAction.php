<?php

namespace App\Actions\Employees;

use App\Models\Employee;

class SyncEmployeePermissionsAction
{
    /**
     * Sync permissions for the given employee.
     *
     * @param  \App\Models\Employee  $employee
     * @param  array  $permissionIds
     * @return void
     */
    public function execute(Employee $employee, array $permissionIds): void
    {
        // sync() expects an array of IDs.
        // It handles attaching/detaching automatically inside a transaction (handled by Eloquent's sync).
        $employee->permissions()->sync($permissionIds);
    }
}
