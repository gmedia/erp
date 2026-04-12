<?php

namespace App\Actions\StockMonitor;

use App\Actions\Concerns\InteractsWithExportableQuery;
use App\Actions\Concerns\InteractsWithStockSnapshotQuery;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class IndexStockMonitorAction
{
    use InteractsWithExportableQuery;
    use InteractsWithStockSnapshotQuery;

    public function execute(IndexStockMonitorRequest $request): array
    {
        $stockValueExpr = $this->stockSnapshotValueExpression();
        $query = $this->buildStockSnapshotQuery();

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
