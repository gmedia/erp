<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\InteractsWithStockSnapshotQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IndexInventoryValuationReportAction
{
    use HandlesReportQuery;
    use InteractsWithStockSnapshotQuery;

    public function execute(IndexInventoryValuationReportRequest $request): LengthAwarePaginator|Collection
    {
        $stockValueExpr = $this->stockSnapshotValueExpression();
        $latestMovements = $this->latestStockMovementIdsQuery();

        $query = StockMovement::query()
            ->whereIn('stock_movements.id', $latestMovements)
            ->with([
                'product.category',
                'product.unit',
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
        $this->applyStockSnapshotSorting($request, $query, $stockValueExpr, 'stock_value');

        return $this->exportOrPaginate($request, $query);
    }
}
