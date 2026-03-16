<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexStockMovementReportAction
{
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

        if ($request->filled('start_date')) {
            $query->whereDate('sm.moved_at', '>=', $request->string('start_date')->toString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sm.moved_at', '<=', $request->string('end_date')->toString());
        }

        if ($request->filled('product_id')) {
            $query->where('sm.product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('sm.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('w.branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.code', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('w.code', 'like', '%' . $search . '%')
                    ->orWhere('b.name', 'like', '%' . $search . '%')
                    ->orWhere('pc.name', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->string('sort_by', 'last_moved_at')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        if ($sortBy === 'product_category_name') {
            $sortBy = 'category_name';
        }

        if (in_array($sortBy, [
            'product_name',
            'warehouse_name',
            'branch_name',
            'category_name',
            'last_moved_at',
        ], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array($sortBy, ['total_in', 'total_out', 'ending_balance'], true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);
        } else {
            $query->orderBy('last_moved_at', 'desc');
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
