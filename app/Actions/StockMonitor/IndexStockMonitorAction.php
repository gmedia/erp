<?php

namespace App\Actions\StockMonitor;

use App\Actions\Concerns\InteractsWithExportableQuery;
use App\Actions\Concerns\InteractsWithStockSnapshotQuery;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndexStockMonitorAction
{
    use InteractsWithExportableQuery;
    use InteractsWithStockSnapshotQuery;

    public function execute(IndexStockMonitorRequest $request): array
    {
        $stockValueExpr = $this->stockSnapshotValueExpression();
        $latestMovements = $this->latestStockMovementIdsQuery();

        $query = StockMovement::query()
            ->whereIn('stock_movements.id', $latestMovements)
            ->with([
                'product.category',
                'warehouse.branch',
            ])
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

        $this->applyStockSnapshotFilters($request, $query);

        if ($request->filled('low_stock_threshold')) {
            $query->where('stock_movements.balance_after', '<=', $request->float('low_stock_threshold'));
        }

        $this->applyStockSnapshotSorting($request, $query, $stockValueExpr, 'quantity_on_hand');

        $summaryRows = (clone $query)->get();
        $stocks = $this->paginateQuery($request, $query);

        return [
            'stocks' => $stocks,
            'summary' => [
                'total_items' => $summaryRows->count(),
                'total_quantity' => (string) $summaryRows->sum('quantity_on_hand'),
                'total_stock_value' => (string) $summaryRows->sum('stock_value'),
                'low_stock_items' => (int) $summaryRows->filter(
                    fn (StockMovement $movement) => (float) $movement->getAttribute('quantity_on_hand')
                            <= (float) $request->float('low_stock_threshold', 10)
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
                'name' => $warehouseName ?: '-',
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
                'name' => $categoryName ?: '-',
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
                'name' => $branchName ?: '-',
                'quantity' => (string) $items->sum('quantity_on_hand'),
                'value' => (string) $items->sum('stock_value'),
            ])
            ->values()
            ->all();
    }
}
