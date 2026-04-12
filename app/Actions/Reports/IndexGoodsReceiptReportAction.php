<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredPurchaseOrderReportIndexAction;
use App\Models\GoodsReceipt;
use Illuminate\Database\Eloquent\Builder;

class IndexGoodsReceiptReportAction extends ConfiguredPurchaseOrderReportIndexAction
{
    protected function buildQuery(): Builder
    {
        return GoodsReceipt::query()
            ->from('goods_receipts as gr')
            ->join('purchase_orders as po', 'gr.purchase_order_id', '=', 'po.id')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->join('warehouses as w', 'gr.warehouse_id', '=', 'w.id')
            ->leftJoin('goods_receipt_items as gri', 'gr.id', '=', 'gri.goods_receipt_id')
            ->leftJoin('products as p', 'gri.product_id', '=', 'p.id')
            ->selectRaw($this->compileSelectColumns([
                'gr.id as goods_receipt_id',
                'gr.gr_number',
                'gr.receipt_date',
                'gr.status',
                'po.id as purchase_order_id',
                'po.po_number',
                ...$this->purchaseOrderPartySelectColumns(),
                'COUNT(DISTINCT gri.id) as item_count',
                'COALESCE(SUM(gri.quantity_received), 0) as total_received_quantity',
                'COALESCE(SUM(gri.quantity_accepted), 0) as total_accepted_quantity',
                'COALESCE(SUM(gri.quantity_rejected), 0) as total_rejected_quantity',
                'COALESCE(SUM(gri.quantity_received * gri.unit_price), 0) as total_receipt_value',
            ]))
            ->groupBy([
                'gr.id',
                'gr.gr_number',
                'gr.receipt_date',
                'gr.status',
                'po.id',
                'po.po_number',
                ...$this->purchaseOrderPartyGroupByColumns(),
            ])
            ->withCasts([
                'receipt_date' => 'date',
                'item_count' => 'integer',
                'total_received_quantity' => 'decimal:2',
                'total_accepted_quantity' => 'decimal:2',
                'total_rejected_quantity' => 'decimal:2',
                'total_receipt_value' => 'decimal:2',
            ]);
    }

    protected function warehouseColumn(): string
    {
        return 'gr.warehouse_id';
    }

    protected function productColumn(): string
    {
        return 'gri.product_id';
    }

    protected function statusColumn(): string
    {
        return 'gr.status';
    }

    protected function dateColumn(): string
    {
        return 'gr.receipt_date';
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [
            'gr.gr_number',
            ...$this->basePurchaseOrderReportSearchColumns(),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'receipt_date';
    }

    /**
     * @return array<string, string>
     */
    protected function sortAliases(): array
    {
        return [
            'goods_receipt_gr_number' => 'gr_number',
            'goods_receipt_receipt_date' => 'receipt_date',
            'goods_receipt_status' => 'status',
            'purchase_order_po_number' => 'po_number',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function plainSortableColumns(): array
    {
        return [
            'gr_number',
            'receipt_date',
            'status',
            'po_number',
            'supplier_name',
            'warehouse_name',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function aggregateSortableColumns(): array
    {
        return [
            'item_count',
            'total_received_quantity',
            'total_accepted_quantity',
            'total_rejected_quantity',
            'total_receipt_value',
        ];
    }
}
