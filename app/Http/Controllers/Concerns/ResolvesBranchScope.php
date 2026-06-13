<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the branch_id that the current request is allowed to view.
 *
 * Policy:
 * - User with `view_all_branches` permission → honor requested branch_id (null = all).
 * - User with employee.branch_id set → forced to own branch (ignore request).
 * - User with employee.branch_id null (no branch assigned) → unscoped (legacy admin).
 */
trait ResolvesBranchScope
{
    /**
     * @param  int|null  $requestedBranchId  branch_id from request query
     * @return int|null null = unscoped; int = forced branch
     */
    protected function resolveBranchScope(?int $requestedBranchId): ?int
    {
        $user = Auth::user();

        if ($this->userCanViewAllBranches($user)) {
            return $requestedBranchId;
        }

        $userBranchId = $this->resolveUserBranchId($user);

        if ($userBranchId === null) {
            return $requestedBranchId;
        }

        return $userBranchId;
    }

    /**
     * @return bool true when the caller is an "all-branches" admin.
     */
    protected function userCanViewAllBranches(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $employee = $user->employee;

        if ($employee === null) {
            return false;
        }

        return $employee->hasPermission('view_all_branches');
    }

    protected function resolveUserBranchId(?User $user): ?int
    {
        return $user?->employee?->branch_id;
    }
}
