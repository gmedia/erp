<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexPurchaseHistoryReportRequest;
use App\Models\PurchaseOrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexPurchaseHistoryReportAction
{
    public function execute(IndexPurchaseHistoryReportRequest $request): LengthAwarePaginator|Collection
    {
        $query = PurchaseOrderItem::query()
            ->from('purchase_order_items as poi')
            ->join('purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->join('warehouses as w', 'po.warehouse_id', '=', 'w.id')
            ->join('products as p', 'poi.product_id', '=', 'p.id')
            ->leftJoin('goods_receipt_items as gri', 'poi.id', '=', 'gri.purchase_order_item_id')
            ->leftJoin('goods_receipts as gr', function ($join) {
                $join->on('gri.goods_receipt_id', '=', 'gr.id')
                    ->where('gr.status', '=', 'confirmed');
            })
            ->selectRaw('
                poi.id as purchase_order_item_id,
                po.id as purchase_order_id,
                po.po_number,
                po.order_date,
                po.expected_delivery_date,
                po.status,
                s.id as supplier_id,
                s.name as supplier_name,
                w.id as warehouse_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                poi.quantity as ordered_quantity,
                COALESCE(
                    SUM(CASE WHEN gr.id IS NOT NULL THEN gri.quantity_received ELSE 0 END),
                    0
                ) as received_quantity,
                poi.quantity - COALESCE(
                    SUM(CASE WHEN gr.id IS NOT NULL THEN gri.quantity_received ELSE 0 END),
                    0
                ) as outstanding_quantity,
                COUNT(DISTINCT gr.id) as receipt_count,
                MAX(gr.receipt_date) as last_receipt_date,
                poi.line_total as total_purchase_value
            ')
            ->groupBy([
                'poi.id',
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                's.id',
                's.name',
                'w.id',
                'w.code',
                'w.name',
                'p.id',
                'p.code',
                'p.name',
                'poi.quantity',
                'poi.line_total',
            ])
            ->withCasts([
                'order_date' => 'date',
                'expected_delivery_date' => 'date',
                'ordered_quantity' => 'decimal:2',
                'received_quantity' => 'decimal:2',
                'outstanding_quantity' => 'decimal:2',
                'total_purchase_value' => 'decimal:2',
                'receipt_count' => 'integer',
                'last_receipt_date' => 'date',
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
                    ->orWhere('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.code', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('w.code', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->string('sort_by', 'order_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if ($sortBy === 'purchase_order_po_number') {
            $sortBy = 'po_number';
        }
        if ($sortBy === 'purchase_order_order_date') {
            $sortBy = 'order_date';
        }
        if ($sortBy === 'purchase_order_expected_delivery_date') {
            $sortBy = 'expected_delivery_date';
        }
        if ($sortBy === 'purchase_order_status') {
            $sortBy = 'status';
        }
        if ($sortBy === 'goods_receipt_last_receipt_date') {
            $sortBy = 'last_receipt_date';
        }

        if (in_array(
            $sortBy,
            [
                'po_number',
                'supplier_name',
                'product_name',
                'product_code',
                'warehouse_name',
                'order_date',
                'expected_delivery_date',
                'status',
                'last_receipt_date',
            ],
            true
        )) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array(
            $sortBy,
            ['ordered_quantity', 'received_quantity', 'outstanding_quantity', 'receipt_count', 'total_purchase_value'],
            true
        )) {
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
