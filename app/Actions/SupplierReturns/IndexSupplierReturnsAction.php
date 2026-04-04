<?php

namespace App\Actions\SupplierReturns;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\SupplierReturns\SupplierReturnFilterService;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use App\Models\SupplierReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSupplierReturnsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private SupplierReturnFilterService $filterService
    ) {}

    public function execute(IndexSupplierReturnRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = SupplierReturn::query()->with([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        $this->applyRequestSearch($request, $query, $this->filterService, [
            'return_number',
            'notes',
        ]);

        $this->applyRequestFilters($request, $query, $this->filterService, [
            'purchase_order_id',
            'goods_receipt_id',
            'supplier_id',
            'warehouse_id',
            'reason',
            'status',
            'return_date_from',
            'return_date_to',
        ]);

        $this->applyMappedIndexSorting($request, $query, $this->filterService, 'created_at', [
            'id',
            'return_number',
            'purchase_order_id',
            'goods_receipt_id',
            'supplier_id',
            'warehouse_id',
            'return_date',
            'reason',
            'status',
            'created_at',
            'updated_at',
        ], [
            'purchase_order' => 'purchase_order_id',
            'goods_receipt' => 'goods_receipt_id',
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
        ]);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
