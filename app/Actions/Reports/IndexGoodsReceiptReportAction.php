<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexGoodsReceiptReportAction
{
    use HandlesReportQuery;

    public function execute(IndexGoodsReceiptReportRequest $request): LengthAwarePaginator|Collection
    {
        $query = GoodsReceipt::query()
            ->from('goods_receipts as gr')
            ->join('purchase_orders as po', 'gr.purchase_order_id', '=', 'po.id')
            ->join('suppliers as s', 'po.supplier_id', '=', 's.id')
            ->join('warehouses as w', 'gr.warehouse_id', '=', 'w.id')
            ->leftJoin('goods_receipt_items as gri', 'gr.id', '=', 'gri.goods_receipt_id')
            ->leftJoin('products as p', 'gri.product_id', '=', 'p.id')
            ->selectRaw('
                gr.id as goods_receipt_id,
                gr.gr_number,
                gr.receipt_date,
                gr.status,
                po.id as purchase_order_id,
                po.po_number,
                s.id as supplier_id,
                s.name as supplier_name,
                w.id as warehouse_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                COUNT(DISTINCT gri.id) as item_count,
                COALESCE(SUM(gri.quantity_received), 0) as total_received_quantity,
                COALESCE(SUM(gri.quantity_accepted), 0) as total_accepted_quantity,
                COALESCE(SUM(gri.quantity_rejected), 0) as total_rejected_quantity,
                COALESCE(SUM(gri.quantity_received * gri.unit_price), 0) as total_receipt_value
            ')
            ->groupBy([
                'gr.id',
                'gr.gr_number',
                'gr.receipt_date',
                'gr.status',
                'po.id',
                'po.po_number',
                's.id',
                's.name',
                'w.id',
                'w.code',
                'w.name',
            ])
            ->withCasts([
                'receipt_date' => 'date',
                'item_count' => 'integer',
                'total_received_quantity' => 'decimal:2',
                'total_accepted_quantity' => 'decimal:2',
                'total_rejected_quantity' => 'decimal:2',
                'total_receipt_value' => 'decimal:2',
            ]);

        $this->applyPurchaseOrderReportFilters($request, $query, 'gr.warehouse_id', 'gri.product_id', 'gr.status', 'gr.receipt_date', [
            'gr.gr_number',
            'po.po_number',
            's.name',
            'w.name',
            'w.code',
            'p.name',
            'p.code',
        ]);

        $this->applyRequestSorting(
            $request,
            $query,
            'receipt_date',
            [
                'goods_receipt_gr_number' => 'gr_number',
                'goods_receipt_receipt_date' => 'receipt_date',
                'goods_receipt_status' => 'status',
                'purchase_order_po_number' => 'po_number',
            ],
            ['gr_number', 'receipt_date', 'status', 'po_number', 'supplier_name', 'warehouse_name'],
            [
                'item_count',
                'total_received_quantity',
                'total_accepted_quantity',
                'total_rejected_quantity',
                'total_receipt_value',
            ],
            'receipt_date',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
