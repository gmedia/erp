<?php

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait InteractsWithAuditTrailIndex
{
    use InteractsWithExportableQuery;

    private function applyCreatedAtDateRange(Request $request, Builder $query): void
    {
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }

    private function applyUserSortableOrdering(
        Request $request,
        Builder $query,
        string $table,
        string $userSortKey,
        string $userForeignKey,
    ): void {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === $userSortKey) {
            $query->leftJoin('users', $userForeignKey, '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select($table . '.*');

            return;
        }

        $query->orderBy($table . '.' . $sortBy, $sortDirection);
    }
}
