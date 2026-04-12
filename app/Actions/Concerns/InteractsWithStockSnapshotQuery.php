<?php

namespace App\Actions\Concerns;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait InteractsWithStockSnapshotQuery
{
    protected function stockSnapshotValueExpression(): string
    {
        return 'stock_movements.balance_after * COALESCE(stock_movements.average_cost_after, products.cost)';
    }

    /**
     * @return Builder<StockMovement>
     */
    protected function buildStockSnapshotQuery(bool $includeProductUnit = false): Builder
    {
        $stockValueExpr = $this->stockSnapshotValueExpression();

        return $this->stockMovementModelClass()::query()
            ->whereIn('stock_movements.id', $this->latestStockMovementIdsQuery())
            ->with($this->stockSnapshotRelations($includeProductUnit))
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('branches', 'warehouses.branch_id', '=', 'branches.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select([
                'stock_movements.*',
                DB::raw('stock_movements.balance_after as quantity_on_hand'),
                DB::raw('COALESCE(stock_movements.average_cost_after, products.cost) as average_cost'),
                DB::raw('(' . $stockValueExpr . ') as stock_value'),
                DB::raw('products.name as product_name'),
                DB::raw('warehouses.name as warehouse_name'),
                DB::raw('branches.name as branch_name'),
                DB::raw('product_categories.name as category_name'),
            ])
            ->withCasts([
                'quantity_on_hand' => 'decimal:2',
                'average_cost' => 'decimal:2',
                'stock_value' => 'decimal:2',
            ]);
    }

    /**
     * @return Builder<StockMovement>
     */
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

    /**
     * @return class-string<StockMovement>
     */
    protected function stockMovementModelClass(): string
    {
        return StockMovement::class;
    }

    /**
     * @return array<int, string>
     */
    private function stockSnapshotRelations(bool $includeProductUnit): array
    {
        $relations = [
            'product.category',
            'warehouse.branch',
        ];

        if ($includeProductUnit) {
            $relations[] = 'product.unit';
        }

        return $relations;
    }
}
