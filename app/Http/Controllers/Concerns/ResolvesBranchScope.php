<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
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
     * Parses the `branch_id` query param from a request and resolves it
     * through the branch-scope policy in a single call.
     *
     * Accepts both raw {@see Request} (query string parsing) and
     * {@see FormRequest} (validated input parsing).
     *
     * @return int|null null = unscoped; int = forced branch
     */
    protected function resolveBranchFromRequest(Request $request): ?int
    {
        if ($request instanceof FormRequest) {
            $requestedBranchId = $request->integer('branch_id') ?: null;
        } else {
            $branchIdRaw = $request->query('branch_id');
            $requestedBranchId = is_numeric($branchIdRaw) ? (int) $branchIdRaw : null;
        }

        return $this->resolveBranchScope($requestedBranchId);
    }

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
        return $user?->employee?->currentEmployment?->branch_id;
    }

    /**
     * Apply branch scope to a query, accounting for models where the
     * branch_id column has been moved to a related table (e.g. Employee).
     *
     * @param  Builder  $query      The Eloquent query to scope.
     * @param  int|null $branchId   The resolved branch ID; null = unscoped.
     * @param  string   $modelClass The fully-qualified model class name.
     * @return Builder
     */
    protected function scoped(Builder $query, ?int $branchId, string $modelClass): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        if ($modelClass === Employee::class) {
            return $query->whereHas('currentEmployment', fn (Builder $q) =>
                $q->where('branch_id', $branchId)
            );
        }

        return $query->where('branch_id', $branchId);
    }
}
