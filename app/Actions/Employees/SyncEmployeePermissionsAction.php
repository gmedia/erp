<?php

namespace App\Actions\Employees;

use App\Actions\SecurityLogs\RecordSecurityLogAction;
use App\Models\Employee;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\DB;

class SyncEmployeePermissionsAction
{
    /**
     * Sync permissions for the given employee.
     */
    public function execute(Employee $employee, array $permissionIds): void
    {
        DB::transaction(function () use ($employee, $permissionIds) {
            $before = $employee->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->sort()->values();

            // sync() expects an array of IDs.
            // It handles attaching/detaching automatically inside a transaction (handled by Eloquent's sync).
            $employee->permissions()->sync($permissionIds);

            $after = collect($permissionIds)->map(fn ($id) => (int) $id)->sort()->values();

            if ($before->all() === $after->all()) {
                return;
            }

            app(RecordSecurityLogAction::class)->execute(
                SecurityLog::EVENT_ROLE_CHANGED,
                userId: $employee->user_id,
                metadata: [
                    'employee_id' => $employee->id,
                    'added' => $after->diff($before)->values()->all(),
                    'removed' => $before->diff($after)->values()->all(),
                ],
                request: request(),
            );
        });
    }
}
