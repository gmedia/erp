<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexStockMovementReportAction
{
    use HandlesReportQuery;

    public function execute(IndexStockMovementReportRequest $request): LengthAwarePaginator|Collection
    {
        $endingBalanceRaw = '(
            SELECT sm2.balance_after
            FROM stock_movements sm2
            WHERE sm2.product_id = sm.product_id
              AND sm2.warehouse_id = sm.warehouse_id';

        $bindings = [];
        if ($request->filled('end_date')) {
            $endingBalanceRaw .= ' AND DATE(sm2.moved_at) <= ?';
            $bindings[] = $request->string('end_date')->toString();
        }
        $endingBalanceRaw .= '
            ORDER BY sm2.moved_at DESC, sm2.id DESC
            LIMIT 1
        ) as ending_balance';

        $query = StockMovement::query()
            ->from('stock_movements as sm')
            ->join('products as p', 'sm.product_id', '=', 'p.id')
            ->join('warehouses as w', 'sm.warehouse_id', '=', 'w.id')
            ->leftJoin('branches as b', 'w.branch_id', '=', 'b.id')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->selectRaw('
                sm.product_id,
                sm.warehouse_id,
                p.code as product_code,
                p.name as product_name,
                p.id as product_entity_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                w.id as warehouse_entity_id,
                b.id as branch_entity_id,
                b.name as branch_name,
                pc.id as category_entity_id,
                pc.name as category_name,
                SUM(sm.quantity_in) as total_in,
                SUM(sm.quantity_out) as total_out,
                MAX(sm.moved_at) as last_moved_at
            ')
            ->selectRaw($endingBalanceRaw, $bindings)
            ->groupBy([
                'sm.product_id',
                'sm.warehouse_id',
                'p.id',
                'p.code',
                'p.name',
                'w.id',
                'w.code',
                'w.name',
                'b.id',
                'b.name',
                'pc.id',
                'pc.name',
            ])
            ->withCasts([
                'total_in' => 'decimal:2',
                'total_out' => 'decimal:2',
                'ending_balance' => 'decimal:2',
                'last_moved_at' => 'datetime',
            ]);

        $this->applyDateRangeFilter($request, $query, 'sm.moved_at');
        $this->applyIntegerFilters($request, $query, [
            'product_id' => 'sm.product_id',
            'warehouse_id' => 'sm.warehouse_id',
            'branch_id' => 'w.branch_id',
            'category_id' => 'p.category_id',
        ]);
        $this->applySearchFilter($request, $query, [
            'p.name',
            'p.code',
            'w.name',
            'w.code',
            'b.name',
            'pc.name',
        ]);
        $this->applyRequestSorting(
            $request,
            $query,
            'last_moved_at',
            ['product_category_name' => 'category_name'],
            ['product_name', 'warehouse_name', 'branch_name', 'category_name', 'last_moved_at'],
            ['total_in', 'total_out', 'ending_balance'],
            'last_moved_at',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
