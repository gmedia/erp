<?php

namespace App\Actions\StockMonitor;

use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndexStockMonitorAction
{
    public function execute(IndexStockMonitorRequest $request): array
    {
        $latestMovements = StockMovement::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id', 'warehouse_id');

        $query = StockMovement::query()
            ->whereIn('stock_movements.id', $latestMovements)
            ->with([
                'product.category',
                'warehouse.branch',
            ])
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select([
                'stock_movements.*',
                DB::raw('stock_movements.balance_after as quantity_on_hand'),
                DB::raw('COALESCE(stock_movements.average_cost_after, products.cost) as average_cost'),
                DB::raw('(stock_movements.balance_after * COALESCE(stock_movements.average_cost_after, products.cost)) as stock_value'),
                DB::raw('products.name as product_name'),
                DB::raw('warehouses.name as warehouse_name'),
                DB::raw('product_categories.name as category_name'),
            ])
            ->withCasts([
                'quantity_on_hand' => 'decimal:2',
                'average_cost' => 'decimal:2',
                'stock_value' => 'decimal:2',
            ]);

        if ($request->filled('product_id')) {
            $query->where('stock_movements.product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('stock_movements.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('warehouse', function (Builder $warehouseQuery) use ($request) {
                $warehouseQuery->where('branch_id', $request->integer('branch_id'));
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function (Builder $productQuery) use ($request) {
                $productQuery->where('category_id', $request->integer('category_id'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('product', function (Builder $productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('warehouse', function (Builder $warehouseQuery) use ($search) {
                        $warehouseQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%')
                            ->orWhereHas('branch', function (Builder $branchQuery) use ($search) {
                                $branchQuery->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        if ($request->filled('low_stock_threshold')) {
            $query->where('stock_movements.balance_after', '<=', $request->float('low_stock_threshold'));
        }

        $sortBy = $request->string('sort_by', 'quantity_on_hand')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if ($sortBy === 'product_name') {
            $query->orderBy('products.name', $sortDirection);
        } elseif ($sortBy === 'warehouse_name') {
            $query->orderBy('warehouses.name', $sortDirection);
        } elseif ($sortBy === 'category_name') {
            $query->orderBy('product_categories.name', $sortDirection);
        } elseif ($sortBy === 'stock_value') {
            $query->orderByRaw('(stock_movements.balance_after * COALESCE(stock_movements.average_cost_after, products.cost)) ' . $sortDirection);
        } elseif ($sortBy === 'quantity_on_hand') {
            $query->orderBy('stock_movements.balance_after', $sortDirection);
        } elseif ($sortBy === 'average_cost') {
            $query->orderByRaw('COALESCE(stock_movements.average_cost_after, products.cost) ' . $sortDirection);
        } else {
            $query->orderBy('stock_movements.' . $sortBy, $sortDirection);
        }

        $summaryRows = (clone $query)->get();
        $stocks = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return [
            'stocks' => $stocks,
            'summary' => [
                'total_items' => $summaryRows->count(),
                'total_quantity' => (string) $summaryRows->sum('quantity_on_hand'),
                'total_stock_value' => (string) $summaryRows->sum('stock_value'),
                'low_stock_items' => (int) $summaryRows->filter(
                    fn (StockMovement $movement) => (float) $movement->quantity_on_hand <= (float) $request->float('low_stock_threshold', 10)
                )->count(),
                'by_warehouse' => $this->buildWarehouseSummary($summaryRows),
                'by_category' => $this->buildCategorySummary($summaryRows),
                'by_branch' => $this->buildBranchSummary($summaryRows),
            ],
        ];
    }

    private function buildWarehouseSummary(Collection $stocks): array
    {
        return $stocks
            ->groupBy('warehouse.name')
            ->map(fn ($items, $warehouseName) => [
                'name' => $warehouseName ?? '-',
                'quantity' => (string) $items->sum('quantity_on_hand'),
                'value' => (string) $items->sum('stock_value'),
            ])
            ->values()
            ->all();
    }

    private function buildCategorySummary(Collection $stocks): array
    {
        return $stocks
            ->groupBy('product.category.name')
            ->map(fn ($items, $categoryName) => [
                'name' => $categoryName ?? '-',
                'quantity' => (string) $items->sum('quantity_on_hand'),
                'value' => (string) $items->sum('stock_value'),
            ])
            ->values()
            ->all();
    }

    private function buildBranchSummary(Collection $stocks): array
    {
        return $stocks
            ->groupBy('warehouse.branch.name')
            ->map(fn ($items, $branchName) => [
                'name' => $branchName ?? '-',
                'quantity' => (string) $items->sum('quantity_on_hand'),
                'value' => (string) $items->sum('stock_value'),
            ])
            ->values()
            ->all();
    }
}
