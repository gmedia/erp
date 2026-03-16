<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexPurchaseOrderStatusReportRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexPurchaseOrderStatusReportAction
{
    public function execute(IndexPurchaseOrderStatusReportRequest $request): LengthAwarePaginator|Collection
    {
        $statusCategorySql = "CASE
            WHEN (COALESCE(SUM(poi.quantity), 0) - COALESCE(SUM(poi.quantity_received), 0)) <= 0
                OR po.status IN ('fully_received', 'closed')
                THEN 'closed'
            WHEN COALESCE(SUM(poi.quantity_received), 0) > 0
                THEN 'partially_received'
            ELSE 'outstanding'
        END";

        $query = PurchaseOrder::query()
            ->from('purchase_orders as po')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->join('warehouses as w', 'po.warehouse_id', '=', 'w.id')
            ->leftJoin('purchase_order_items as poi', 'po.id', '=', 'poi.purchase_order_id')
            ->leftJoin('products as p', 'poi.product_id', '=', 'p.id')
            ->selectRaw('
                po.id,
                po.po_number,
                po.order_date,
                po.expected_delivery_date,
                po.status,
                po.grand_total,
                s.id as supplier_id,
                s.name as supplier_name,
                w.id as warehouse_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                COUNT(DISTINCT poi.id) as item_count,
                COALESCE(SUM(poi.quantity), 0) as ordered_quantity,
                COALESCE(SUM(poi.quantity_received), 0) as received_quantity,
                COALESCE(SUM(poi.quantity), 0) - COALESCE(SUM(poi.quantity_received), 0) as outstanding_quantity
            ')
            ->selectRaw($statusCategorySql . ' as status_category')
            ->selectRaw('
                CASE
                    WHEN COALESCE(SUM(poi.quantity), 0) = 0 THEN 0
                    ELSE (COALESCE(SUM(poi.quantity_received), 0) / COALESCE(SUM(poi.quantity), 0)) * 100
                END as receipt_progress_percent
            ')
            ->groupBy([
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                'po.grand_total',
                's.id',
                's.name',
                'w.id',
                'w.code',
                'w.name',
            ])
            ->withCasts([
                'order_date' => 'date',
                'expected_delivery_date' => 'date',
                'grand_total' => 'decimal:2',
                'ordered_quantity' => 'decimal:2',
                'received_quantity' => 'decimal:2',
                'outstanding_quantity' => 'decimal:2',
                'receipt_progress_percent' => 'decimal:2',
                'item_count' => 'integer',
            ]);

        if ($request->filled('supplier_id')) {
            $query->where('po.supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('po.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('poi.product_id', $request->integer('product_id'));
        }

        if ($request->filled('status')) {
            $query->where('po.status', $request->string('status')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('po.order_date', '>=', $request->string('start_date')->toString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('po.order_date', '<=', $request->string('end_date')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('po.po_number', 'like', '%' . $search . '%')
                    ->orWhere('s.name', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('w.code', 'like', '%' . $search . '%')
                    ->orWhere('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.code', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status_category')) {
            $query->having('status_category', '=', $request->string('status_category')->toString());
        }

        $sortBy = $request->string('sort_by', 'order_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        if ($sortBy === 'purchase_order_po_number') {
            $sortBy = 'po_number';
        }
        if ($sortBy === 'purchase_order_expected_delivery_date') {
            $sortBy = 'expected_delivery_date';
        }
        if ($sortBy === 'purchase_order_status') {
            $sortBy = 'status';
        }
        if ($sortBy === 'purchase_order_status_category') {
            $sortBy = 'status_category';
        }

        if (in_array($sortBy, [
            'po_number',
            'supplier_name',
            'warehouse_name',
            'order_date',
            'expected_delivery_date',
            'status',
            'status_category',
        ], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array($sortBy, [
            'ordered_quantity',
            'received_quantity',
            'outstanding_quantity',
            'receipt_progress_percent',
            'grand_total',
        ], true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);
        } else {
            $query->orderBy('order_date', 'desc');
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
