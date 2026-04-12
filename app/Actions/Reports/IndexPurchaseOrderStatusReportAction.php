<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredPurchaseOrderReportIndexAction;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class IndexPurchaseOrderStatusReportAction extends ConfiguredPurchaseOrderReportIndexAction
{
    protected function buildQuery(): Builder
    {
        return PurchaseOrder::query()
            ->from('purchase_orders as po')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->join('warehouses as w', 'po.warehouse_id', '=', 'w.id')
            ->leftJoin('purchase_order_items as poi', 'po.id', '=', 'poi.purchase_order_id')
            ->leftJoin('products as p', 'poi.product_id', '=', 'p.id')
            ->selectRaw($this->compileSelectColumns([
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                'po.grand_total',
                ...$this->purchaseOrderPartySelectColumns(),
                'COUNT(DISTINCT poi.id) as item_count',
                'COALESCE(SUM(poi.quantity), 0) as ordered_quantity',
                'COALESCE(SUM(poi.quantity_received), 0) as received_quantity',
                'COALESCE(SUM(poi.quantity), 0) - COALESCE(SUM(poi.quantity_received), 0) as outstanding_quantity',
            ]))
            ->selectRaw($this->statusCategorySelectSql() . ' as status_category')
            ->selectRaw($this->receiptProgressPercentSelectSql())
            ->groupBy([
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                'po.grand_total',
                ...$this->purchaseOrderPartyGroupByColumns(),
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
    }

    protected function warehouseColumn(): string
    {
        return 'po.warehouse_id';
    }

    protected function productColumn(): string
    {
        return 'poi.product_id';
    }

    protected function statusColumn(): string
    {
        return 'po.status';
    }

    protected function dateColumn(): string
    {
        return 'po.order_date';
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return $this->basePurchaseOrderReportSearchColumns();
    }

    protected function defaultSortBy(): string
    {
        return 'order_date';
    }

    /**
     * @return array<string, string>
     */
    protected function sortAliases(): array
    {
        return [
            'purchase_order_po_number' => 'po_number',
            'purchase_order_expected_delivery_date' => 'expected_delivery_date',
            'purchase_order_status' => 'status',
            'purchase_order_status_category' => 'status_category',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function plainSortableColumns(): array
    {
        return [
            'po_number',
            'supplier_name',
            'warehouse_name',
            'order_date',
            'expected_delivery_date',
            'status',
            'status_category',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function aggregateSortableColumns(): array
    {
        return [
            'ordered_quantity',
            'received_quantity',
            'outstanding_quantity',
            'receipt_progress_percent',
            'grand_total',
        ];
    }

    protected function applyAdditionalFilters(FormRequest $request, Builder $query): void
    {
        if ($request->filled('status_category')) {
            $query->having('status_category', '=', $request->string('status_category')->toString());
        }
    }

    private function statusCategorySelectSql(): string
    {
        return "CASE
            WHEN (COALESCE(SUM(poi.quantity), 0) - COALESCE(SUM(poi.quantity_received), 0)) <= 0
                OR po.status IN ('fully_received', 'closed')
                THEN 'closed'
            WHEN COALESCE(SUM(poi.quantity_received), 0) > 0
                THEN 'partially_received'
            ELSE 'outstanding'
        END";
    }

    private function receiptProgressPercentSelectSql(): string
    {
        return 'CASE
                    WHEN COALESCE(SUM(poi.quantity), 0) = 0 THEN 0
                    ELSE (COALESCE(SUM(poi.quantity_received), 0) / COALESCE(SUM(poi.quantity), 0)) * 100
                END as receipt_progress_percent';
    }
}
