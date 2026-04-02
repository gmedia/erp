<?php

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait InteractsWithStockSnapshotQuery
{
    protected function stockSnapshotValueExpression(): string
    {
        return 'stock_movements.balance_after * COALESCE(stock_movements.average_cost_after, products.cost)';
    }

    protected function latestStockMovementIdsQuery(): Builder
    {
        return $this->stockMovementModelClass()::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id', 'warehouse_id');
    }

    protected function applyStockSnapshotFilters(Request $request, Builder $query): void
    {
        if ($request->filled('product_id')) {
            $query->where('stock_movements.product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('stock_movements.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('warehouses.branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->integer('category_id'));
        }

        if (! $request->filled('search')) {
            return;
        }

        $search = $request->string('search')->toString();
        $query->where(function (Builder $builder) use ($search) {
            $builder->where('products.name', 'like', '%' . $search . '%')
                ->orWhere('products.code', 'like', '%' . $search . '%')
                ->orWhere('warehouses.name', 'like', '%' . $search . '%')
                ->orWhere('warehouses.code', 'like', '%' . $search . '%')
                ->orWhere('branches.name', 'like', '%' . $search . '%')
                ->orWhere('product_categories.name', 'like', '%' . $search . '%');
        });
    }

    protected function applyStockSnapshotSorting(
        Request $request,
        Builder $query,
        string $stockValueExpr,
        string $defaultSortBy
    ): void {
        $sortBy = $request->string('sort_by', $defaultSortBy)->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if ($sortBy === 'product_category_name') {
            $sortBy = 'category_name';
        }

        if ($sortBy === 'product_name') {
            $query->orderBy('products.name', $sortDirection);

            return;
        }

        if ($sortBy === 'warehouse_name') {
            $query->orderBy('warehouses.name', $sortDirection);

            return;
        }

        if ($sortBy === 'branch_name') {
            $query->orderBy('branches.name', $sortDirection);

            return;
        }

        if ($sortBy === 'category_name') {
            $query->orderBy('product_categories.name', $sortDirection);

            return;
        }

        if ($sortBy === 'stock_value') {
            $query->orderByRaw($stockValueExpr . ' ' . $sortDirection);

            return;
        }

        if ($sortBy === 'quantity_on_hand') {
            $query->orderBy('stock_movements.balance_after', $sortDirection);

            return;
        }

        if ($sortBy === 'average_cost') {
            $query->orderByRaw('COALESCE(stock_movements.average_cost_after, products.cost) ' . $sortDirection);

            return;
        }

        $query->orderBy('stock_movements.' . $sortBy, $sortDirection);
    }

    protected function stockMovementModelClass(): string
    {
        return \App\Models\StockMovement::class;
    }
}
