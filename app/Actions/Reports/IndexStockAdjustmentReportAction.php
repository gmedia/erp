<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use App\Models\StockAdjustmentItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexStockAdjustmentReportAction
{
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

        if ($request->filled('warehouse_id')) {
            $query->where('sa.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('w.branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('adjustment_type')) {
            $query->where('sa.adjustment_type', $request->string('adjustment_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('sa.status', $request->string('status')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('sa.adjustment_date', '>=', $request->string('start_date')->toString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sa.adjustment_date', '<=', $request->string('end_date')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('sa.adjustment_number', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('w.code', 'like', '%' . $search . '%')
                    ->orWhere('b.name', 'like', '%' . $search . '%')
                    ->orWhere('sa.adjustment_type', 'like', '%' . $search . '%')
                    ->orWhere('sa.status', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->string('sort_by', 'adjustment_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if (in_array($sortBy, [
            'adjustment_date',
            'adjustment_type',
            'status',
            'warehouse_name',
            'branch_name',
        ], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array($sortBy, ['total_quantity_adjusted', 'total_adjustment_value', 'adjustment_count'], true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);
        } else {
            $query->orderBy('adjustment_date', 'desc');
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
