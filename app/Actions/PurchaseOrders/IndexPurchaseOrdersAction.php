<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\PurchaseOrders\PurchaseOrderFilterService;
use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseOrdersAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private PurchaseOrderFilterService $filterService
    ) {}

    public function execute(IndexPurchaseOrderRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = PurchaseOrder::query()->with([
            'supplier',
            'warehouse',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        $this->applyRequestSearch($request, $query, $this->filterService, [
            'po_number',
            'payment_terms',
            'notes',
            'shipping_address',
        ]);

        $this->applyRequestFilters($request, $query, $this->filterService, [
            'supplier_id',
            'warehouse_id',
            'status',
            'currency',
            'order_date_from',
            'order_date_to',
            'expected_delivery_date_from',
            'expected_delivery_date_to',
            'grand_total_min',
            'grand_total_max',
        ]);

        $this->applyMappedIndexSorting($request, $query, $this->filterService, 'created_at', [
            'id',
            'po_number',
            'supplier_id',
            'warehouse_id',
            'order_date',
            'expected_delivery_date',
            'currency',
            'status',
            'grand_total',
            'created_at',
            'updated_at',
        ], [
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
        ]);

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
