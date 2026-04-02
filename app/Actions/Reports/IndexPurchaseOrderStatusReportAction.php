<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexPurchaseOrderStatusReportRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexPurchaseOrderStatusReportAction
{
    use HandlesReportQuery;

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

        $this->applyPurchaseOrderReportFilters($request, $query, 'po.warehouse_id', 'poi.product_id', 'po.status', 'po.order_date', [
            'po.po_number',
            's.name',
            'w.name',
            'w.code',
            'p.name',
            'p.code',
        ]);

        if ($request->filled('status_category')) {
            $query->having('status_category', '=', $request->string('status_category')->toString());
        }

        $this->applyRequestSorting(
            $request,
            $query,
            'order_date',
            [
                'purchase_order_po_number' => 'po_number',
                'purchase_order_expected_delivery_date' => 'expected_delivery_date',
                'purchase_order_status' => 'status',
                'purchase_order_status_category' => 'status_category',
            ],
            [
                'po_number',
                'supplier_name',
                'warehouse_name',
                'order_date',
                'expected_delivery_date',
                'status',
                'status_category',
            ],
            [
                'ordered_quantity',
                'received_quantity',
                'outstanding_quantity',
                'receipt_progress_percent',
                'grand_total',
            ],
            'order_date',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
