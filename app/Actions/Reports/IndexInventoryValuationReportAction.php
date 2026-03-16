<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IndexInventoryValuationReportAction
{
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

        if ($request->filled('search')) {
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

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
