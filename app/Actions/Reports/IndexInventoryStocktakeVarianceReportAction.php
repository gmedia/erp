<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use App\Models\InventoryStocktakeItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexInventoryStocktakeVarianceReportAction
{
    use HandlesReportQuery;

    public function execute(IndexInventoryStocktakeVarianceReportRequest $request): LengthAwarePaginator|Collection
    {
        $query = InventoryStocktakeItem::query()
            ->from('inventory_stocktake_items as isi')
            ->join('inventory_stocktakes as st', 'isi.inventory_stocktake_id', '=', 'st.id')
            ->join('products as p', 'isi.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.product_category_id', '=', 'pc.id')
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

        $this->applyIntegerFilters($request, $query, [
            'inventory_stocktake_id' => 'isi.inventory_stocktake_id',
            'product_id' => 'isi.product_id',
            'warehouse_id' => 'st.warehouse_id',
            'branch_id' => 'w.branch_id',
            'category_id' => 'p.product_category_id',
        ]);
        $this->applyStringFilters($request, $query, [
            'result' => 'isi.result',
        ]);
        $this->applyDateRangeFilter($request, $query, 'st.stocktake_date');
        $this->applySearchFilter($request, $query, [
            'st.stocktake_number',
            'p.code',
            'p.name',
            'pc.name',
            'w.name',
            'b.name',
        ]);
        $this->applyRequestSorting(
            $request,
            $query,
            'counted_at',
            [],
            [
                'stocktake_number',
                'stocktake_date',
                'product_name',
                'product_code',
                'category_name',
                'warehouse_name',
                'branch_name',
                'result',
                'counted_at',
                'counted_by_name',
            ],
            ['system_quantity', 'counted_quantity', 'variance'],
            'counted_at',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
