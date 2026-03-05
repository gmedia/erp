<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use App\Models\InventoryStocktakeItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexInventoryStocktakeVarianceReportAction
{
    public function execute(IndexInventoryStocktakeVarianceReportRequest $request): LengthAwarePaginator|Collection
    {
        $query = InventoryStocktakeItem::query()
            ->from('inventory_stocktake_items as isi')
            ->join('inventory_stocktakes as st', 'isi.inventory_stocktake_id', '=', 'st.id')
            ->join('products as p', 'isi.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->join('warehouses as w', 'st.warehouse_id', '=', 'w.id')
            ->leftJoin('branches as b', 'w.branch_id', '=', 'b.id')
            ->leftJoin('users as u', 'isi.counted_by', '=', 'u.id')
            ->selectRaw('
                isi.id,
                isi.inventory_stocktake_id,
                st.stocktake_number,
                st.stocktake_date,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                pc.id as category_id,
                pc.name as category_name,
                w.id as warehouse_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                b.id as branch_id,
                b.name as branch_name,
                isi.system_quantity,
                isi.counted_quantity,
                isi.variance,
                isi.result,
                isi.counted_at,
                u.id as counted_by_id,
                u.name as counted_by_name
            ')
            ->withCasts([
                'stocktake_date' => 'date',
                'system_quantity' => 'decimal:2',
                'counted_quantity' => 'decimal:2',
                'variance' => 'decimal:2',
                'counted_at' => 'datetime',
            ])
            ->whereNotNull('isi.variance')
            ->where('isi.variance', '<>', 0);

        if ($request->filled('inventory_stocktake_id')) {
            $query->where('isi.inventory_stocktake_id', $request->integer('inventory_stocktake_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('isi.product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('st.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('w.branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->integer('category_id'));
        }

        if ($request->filled('result')) {
            $query->where('isi.result', $request->string('result')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('st.stocktake_date', '>=', $request->string('start_date')->toString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('st.stocktake_date', '<=', $request->string('end_date')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('st.stocktake_number', 'like', '%' . $search . '%')
                    ->orWhere('p.code', 'like', '%' . $search . '%')
                    ->orWhere('p.name', 'like', '%' . $search . '%')
                    ->orWhere('pc.name', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('b.name', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->string('sort_by', 'counted_at')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if (in_array($sortBy, ['stocktake_number', 'stocktake_date', 'product_name', 'product_code', 'category_name', 'warehouse_name', 'branch_name', 'result', 'counted_at', 'counted_by_name'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array($sortBy, ['system_quantity', 'counted_quantity', 'variance'], true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);
        } else {
            $query->orderBy('counted_at', 'desc');
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
