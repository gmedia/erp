<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use App\Models\StockAdjustmentItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexStockAdjustmentReportAction
{
    use HandlesReportQuery;

    public function execute(IndexStockAdjustmentReportRequest $request): LengthAwarePaginator|Collection
    {
        $query = StockAdjustmentItem::query()
            ->from('stock_adjustment_items as sai')
            ->join('stock_adjustments as sa', 'sai.stock_adjustment_id', '=', 'sa.id')
            ->join('warehouses as w', 'sa.warehouse_id', '=', 'w.id')
            ->leftJoin('branches as b', 'w.branch_id', '=', 'b.id')
            ->selectRaw('
                sa.adjustment_date,
                sa.adjustment_type,
                sa.status,
                w.id as warehouse_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                b.id as branch_id,
                b.name as branch_name,
                COUNT(DISTINCT sa.id) as adjustment_count,
                SUM(sai.quantity_adjusted) as total_quantity_adjusted,
                SUM(sai.total_cost) as total_adjustment_value
            ')
            ->groupBy([
                'sa.adjustment_date',
                'sa.adjustment_type',
                'sa.status',
                'w.id',
                'w.code',
                'w.name',
                'b.id',
                'b.name',
            ])
            ->withCasts([
                'adjustment_date' => 'date',
                'adjustment_count' => 'integer',
                'total_quantity_adjusted' => 'decimal:2',
                'total_adjustment_value' => 'decimal:2',
            ]);

        $this->applyIntegerFilters($request, $query, [
            'warehouse_id' => 'sa.warehouse_id',
            'branch_id' => 'w.branch_id',
        ]);
        $this->applyStringFilters($request, $query, [
            'adjustment_type' => 'sa.adjustment_type',
            'status' => 'sa.status',
        ]);
        $this->applyDateRangeFilter($request, $query, 'sa.adjustment_date');
        $this->applySearchFilter($request, $query, [
            'sa.adjustment_number',
            'w.name',
            'w.code',
            'b.name',
            'sa.adjustment_type',
            'sa.status',
        ]);
        $this->applyRequestSorting(
            $request,
            $query,
            'adjustment_date',
            [],
            ['adjustment_date', 'adjustment_type', 'status', 'warehouse_name', 'branch_name'],
            ['total_quantity_adjusted', 'total_adjustment_value', 'adjustment_count'],
            'adjustment_date',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
