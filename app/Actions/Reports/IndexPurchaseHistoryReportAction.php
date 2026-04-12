<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredPurchaseOrderReportIndexAction;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Builder;

class IndexPurchaseHistoryReportAction extends ConfiguredPurchaseOrderReportIndexAction
{
    protected function buildQuery(): Builder
    {
        return PurchaseOrderItem::query()
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
            ->selectRaw($this->compileSelectColumns([
                'poi.id as purchase_order_item_id',
                'po.id as purchase_order_id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                ...$this->purchaseOrderPartySelectColumns(),
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                'poi.quantity as ordered_quantity',
                $this->receivedQuantitySelectSql(),
                $this->outstandingQuantitySelectSql(),
                'COUNT(DISTINCT gr.id) as receipt_count',
                'MAX(gr.receipt_date) as last_receipt_date',
                'poi.line_total as total_purchase_value',
            ]))
            ->groupBy([
                'poi.id',
                'po.id',
                'po.po_number',
                'po.order_date',
                'po.expected_delivery_date',
                'po.status',
                ...$this->purchaseOrderPartyGroupByColumns(),
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
            'purchase_order_order_date' => 'order_date',
            'purchase_order_expected_delivery_date' => 'expected_delivery_date',
            'purchase_order_status' => 'status',
            'goods_receipt_last_receipt_date' => 'last_receipt_date',
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
            'product_name',
            'product_code',
            'warehouse_name',
            'order_date',
            'expected_delivery_date',
            'status',
            'last_receipt_date',
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
            'receipt_count',
            'total_purchase_value',
        ];
    }

    private function receivedQuantitySelectSql(): string
    {
        return <<<'SQL'
COALESCE(
                    SUM(CASE WHEN gr.id IS NOT NULL THEN gri.quantity_received ELSE 0 END),
                    0
                ) as received_quantity
SQL;
    }

    private function outstandingQuantitySelectSql(): string
    {
        return <<<'SQL'
poi.quantity - COALESCE(
                    SUM(CASE WHEN gr.id IS NOT NULL THEN gri.quantity_received ELSE 0 END),
                    0
                ) as outstanding_quantity
SQL;
    }
}
