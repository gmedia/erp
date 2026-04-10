<?php

namespace App\Actions\Reports\Concerns;

use App\Actions\Concerns\InteractsWithExportableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesReportQuery
{
    use InteractsWithExportableQuery;

    protected function applyAssetRelationIntegerFilter(
        Request $request,
        Builder $query,
        string $requestKey,
        string $column,
    ): void {
        if (! $request->filled($requestKey)) {
            return;
        }

        $query->whereHas('asset', function (Builder $assetQuery) use ($request, $column, $requestKey) {
            $assetQuery->where($column, $request->integer($requestKey));
        });
    }

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

    /**
     * @param  array<string, string>  $columns
     */
    protected function applyIntegerFilters(Request $request, Builder $query, array $columns): void
    {
        foreach ($columns as $requestKey => $column) {
            $this->applyIntegerFilter($request, $query, $requestKey, $column);
        }
    }

    /**
     * @param  array<string, string>  $columns
     */
    protected function applyStringFilters(Request $request, Builder $query, array $columns): void
    {
        foreach ($columns as $requestKey => $column) {
            $this->applyStringFilter($request, $query, $requestKey, $column);
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
     * @param  array<int, string>  $searchColumns
     */
    protected function applyPurchaseOrderReportFilters(
        Request $request,
        Builder $query,
        string $warehouseColumn,
        string $productColumn,
        string $statusColumn,
        string $dateColumn,
        array $searchColumns
    ): void {
        $this->applyIntegerFilter($request, $query, 'supplier_id', 'po.supplier_id');
        $this->applyIntegerFilter($request, $query, 'warehouse_id', $warehouseColumn);
        $this->applyIntegerFilter($request, $query, 'product_id', $productColumn);
        $this->applyStringFilter($request, $query, 'status', $statusColumn);
        $this->applyDateRangeFilter($request, $query, $dateColumn);
        $this->applySearchFilter($request, $query, $searchColumns);
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

    /**
     * @param  array<string, string>  $aliases
     * @param  array<int, string>  $plainSortable
     * @param  array<int, string>  $aggregateSortable
     */
    protected function applyRequestSorting(
        Request $request,
        Builder $query,
        string $defaultSortBy,
        array $aliases,
        array $plainSortable,
        array $aggregateSortable,
        string $fallbackSortBy,
        string $fallbackSortDirection = 'desc'
    ): void {
        $sortBy = $request->string('sort_by', $defaultSortBy)->toString();
        $sortDirection = $request->string('sort_direction', $fallbackSortDirection)->toString();
        $sortBy = $this->normalizeSortBy($sortBy, $aliases);

        $this->applySorting(
            $query,
            $sortBy,
            $sortDirection,
            $plainSortable,
            $aggregateSortable,
            $fallbackSortBy,
            $fallbackSortDirection,
        );
    }

    protected function applyMaintenanceCostReportFilters(Request $request, Builder $query): void
    {
        $this->applyDateRangeFilter($request, $query, 'performed_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('asset', function (Builder $assetQuery) use ($search) {
                    $assetQuery->where('asset_code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                })->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        $this->applyAssetRelationIntegerFilter($request, $query, 'asset_category_id', 'asset_category_id');
        $this->applyAssetRelationIntegerFilter($request, $query, 'branch_id', 'branch_id');
        $this->applyIntegerFilter($request, $query, 'supplier_id', 'supplier_id');
        $this->applyStringFilters($request, $query, [
            'maintenance_type' => 'maintenance_type',
            'status' => 'status',
        ]);
    }

    protected function applyMaintenanceCostReportSorting(Request $request, Builder $query): void
    {
        $sortBy = $request->string('sort_by', 'performed_at')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if (in_array($sortBy, ['asset_code', 'asset_name'], true)) {
            $query->join('assets', 'asset_maintenances.asset_id', '=', 'assets.id')
                ->orderBy($sortBy === 'asset_name' ? 'assets.name' : 'assets.asset_code', $sortDirection)
                ->select('asset_maintenances.*');

            return;
        }

        if ($sortBy === 'supplier_name') {
            $query->leftJoin('suppliers', 'asset_maintenances.supplier_id', '=', 'suppliers.id')
                ->orderBy('suppliers.name', $sortDirection)
                ->select('asset_maintenances.*');

            return;
        }

        $query->orderBy('asset_maintenances.' . $sortBy, $sortDirection);
    }
}
