<?php

namespace App\Actions\GoodsReceipts;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\GoodsReceipts\GoodsReceiptFilterService;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexGoodsReceiptsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private GoodsReceiptFilterService $filterService
    ) {}

    public function execute(IndexGoodsReceiptRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = GoodsReceipt::query()->with([
            'purchaseOrder.supplier',
            'warehouse',
            'receiver',
            'confirmer',
            'creator',
            'items.product',
            'items.unit',
        ]);

        $this->applyRequestSearch($request, $query, $this->filterService, [
            'gr_number',
            'supplier_delivery_note',
            'notes',
        ]);

        $this->applyRequestFilters($request, $query, $this->filterService, [
            'purchase_order_id',
            'warehouse_id',
            'status',
            'received_by',
            'receipt_date_from',
            'receipt_date_to',
        ]);

        $this->applyMappedIndexSorting($request, $query, $this->filterService, 'created_at', [
            'id',
            'gr_number',
            'purchase_order_id',
            'warehouse_id',
            'receipt_date',
            'supplier_delivery_note',
            'status',
            'created_at',
            'updated_at',
        ], [
            'purchase_order' => 'purchase_order_id',
            'warehouse' => 'warehouse_id',
        ]);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
