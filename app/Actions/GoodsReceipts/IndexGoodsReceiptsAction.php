<?php

namespace App\Actions\GoodsReceipts;

use App\Domain\GoodsReceipts\GoodsReceiptFilterService;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexGoodsReceiptsAction
{
    public function __construct(
        private GoodsReceiptFilterService $filterService
    ) {
    }

    public function execute(IndexGoodsReceiptRequest $request): LengthAwarePaginator
    {
        $query = GoodsReceipt::query()->with([
            'purchaseOrder.supplier',
            'warehouse',
            'receiver',
            'confirmer',
            'creator',
            'items.product',
            'items.unit',
        ]);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->string('search')->toString(), [
                'gr_number',
                'supplier_delivery_note',
                'notes',
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'purchase_order_id' => $request->get('purchase_order_id'),
            'warehouse_id' => $request->get('warehouse_id'),
            'status' => $request->get('status'),
            'received_by' => $request->get('received_by'),
            'receipt_date_from' => $request->get('receipt_date_from'),
            'receipt_date_to' => $request->get('receipt_date_to'),
        ]);

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = strtolower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'purchase_order' => 'purchase_order_id',
            'warehouse' => 'warehouse_id',
        ];

        $sortBy = $sortMap[$sortBy] ?? $sortBy;

        $this->filterService->applySorting($query, $sortBy, $sortDirection, [
            'id',
            'gr_number',
            'purchase_order_id',
            'warehouse_id',
            'receipt_date',
            'supplier_delivery_note',
            'status',
            'created_at',
            'updated_at',
        ]);

        return $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1),
        );
    }
}
