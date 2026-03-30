<?php

namespace App\Actions\Reports\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait HandlesReportQuery
{
    protected function applyIntegerFilter(Request $request, Builder $query, string $requestKey, string $column): void
    {
        if ($request->filled($requestKey)) {
            $query->where($column, $request->integer($requestKey));
        }
    }

    protected function applyStringFilter(Request $request, Builder $query, string $requestKey, string $column): void
    {
        if ($request->filled($requestKey)) {
            $query->where($column, $request->string($requestKey)->toString());
        }
    }

    protected function applyDateRangeFilter(
        Request $request,
        Builder $query,
        string $column,
        string $startKey = 'start_date',
        string $endKey = 'end_date'
    ): void {
        if ($request->filled($startKey)) {
            $query->whereDate($column, '>=', $request->string($startKey)->toString());
        }

        if ($request->filled($endKey)) {
            $query->whereDate($column, '<=', $request->string($endKey)->toString());
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function applySearchFilter(Request $request, Builder $query, array $columns): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = $request->string('search')->toString();
        $query->where(function (Builder $builder) use ($search, $columns) {
            foreach ($columns as $column) {
                $builder->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    /**
     * @param  array<string, string>  $aliases
     */
    protected function normalizeSortBy(string $sortBy, array $aliases): string
    {
        return $aliases[$sortBy] ?? $sortBy;
    }

    /**
     * @param  array<int, string>  $plainSortable
     * @param  array<int, string>  $aggregateSortable
     */
    protected function applySorting(
        Builder $query,
        string $sortBy,
        string $sortDirection,
        array $plainSortable,
        array $aggregateSortable,
        string $fallbackSortBy,
        string $fallbackSortDirection = 'desc'
    ): void {
        if (in_array($sortBy, $plainSortable, true)) {
            $query->orderBy($sortBy, $sortDirection);

            return;
        }

        if (in_array($sortBy, $aggregateSortable, true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);

            return;
        }

        $query->orderBy($fallbackSortBy, $fallbackSortDirection);
    }

    protected function exportOrPaginate(Request $request, Builder $query): LengthAwarePaginator|Collection
    {
        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
