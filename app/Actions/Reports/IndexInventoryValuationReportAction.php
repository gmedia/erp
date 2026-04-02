<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IndexInventoryValuationReportAction
{
    use HandlesReportQuery;

    public function execute(IndexInventoryValuationReportRequest $request): LengthAwarePaginator|Collection
    {
        $stockValueExpr = 'stock_movements.balance_after * COALESCE(stock_movements.average_cost_after, products.cost)';

        $latestMovements = StockMovement::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id', 'warehouse_id');

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

        $this->applyIntegerFilters($request, $query, [
            'product_id' => 'stock_movements.product_id',
            'warehouse_id' => 'stock_movements.warehouse_id',
            'branch_id' => 'warehouses.branch_id',
            'category_id' => 'products.category_id',
        ]);
        $this->applySearchFilter($request, $query, [
            'products.name',
            'products.code',
            'warehouses.name',
            'warehouses.code',
            'branches.name',
            'product_categories.name',
        ]);

        $sortBy = $request->string('sort_by', 'stock_value')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if ($sortBy === 'product_name') {
            $query->orderBy('products.name', $sortDirection);
        } elseif ($sortBy === 'warehouse_name') {
            $query->orderBy('warehouses.name', $sortDirection);
        } elseif ($sortBy === 'branch_name') {
            $query->orderBy('branches.name', $sortDirection);
        } elseif ($sortBy === 'category_name') {
            $query->orderBy('product_categories.name', $sortDirection);
        } elseif ($sortBy === 'stock_value') {
            $query->orderByRaw($stockValueExpr . ' ' . $sortDirection);
        } elseif ($sortBy === 'quantity_on_hand') {
            $query->orderBy('stock_movements.balance_after', $sortDirection);
        } elseif ($sortBy === 'average_cost') {
            $query->orderByRaw('COALESCE(stock_movements.average_cost_after, products.cost) ' . $sortDirection);
        } else {
            $query->orderBy('stock_movements.' . $sortBy, $sortDirection);
        }

        return $this->exportOrPaginate($request, $query);
    }
}
