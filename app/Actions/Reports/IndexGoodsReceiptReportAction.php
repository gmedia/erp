<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexGoodsReceiptReportAction
{
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

        if ($request->filled('supplier_id')) {
            $query->where('po.supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('gr.warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('gri.product_id', $request->integer('product_id'));
        }

        if ($request->filled('status')) {
            $query->where('gr.status', $request->string('status')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('gr.receipt_date', '>=', $request->string('start_date')->toString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('gr.receipt_date', '<=', $request->string('end_date')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('gr.gr_number', 'like', '%' . $search . '%')
                    ->orWhere('po.po_number', 'like', '%' . $search . '%')
                    ->orWhere('s.name', 'like', '%' . $search . '%')
                    ->orWhere('w.name', 'like', '%' . $search . '%')
                    ->orWhere('w.code', 'like', '%' . $search . '%')
                    ->orWhere('p.name', 'like', '%' . $search . '%')
                    ->orWhere('p.code', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->string('sort_by', 'receipt_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        if ($sortBy === 'goods_receipt_gr_number') {
            $sortBy = 'gr_number';
        }
        if ($sortBy === 'goods_receipt_receipt_date') {
            $sortBy = 'receipt_date';
        }
        if ($sortBy === 'goods_receipt_status') {
            $sortBy = 'status';
        }
        if ($sortBy === 'purchase_order_po_number') {
            $sortBy = 'po_number';
        }

        if (in_array($sortBy, [
            'gr_number',
            'receipt_date',
            'status',
            'po_number',
            'supplier_name',
            'warehouse_name',
        ], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif (in_array($sortBy, [
            'item_count',
            'total_received_quantity',
            'total_accepted_quantity',
            'total_rejected_quantity',
            'total_receipt_value',
        ], true)) {
            $query->orderByRaw($sortBy . ' ' . $sortDirection);
        } else {
            $query->orderBy('receipt_date', 'desc');
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }
}
