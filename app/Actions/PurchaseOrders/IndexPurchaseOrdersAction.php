<?php

namespace App\Actions\PurchaseOrders;

use App\Domain\PurchaseOrders\PurchaseOrderFilterService;
use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseOrdersAction
{
    public function __construct(
        private PurchaseOrderFilterService $filterService
    ) {}

    public function execute(IndexPurchaseOrderRequest $request): LengthAwarePaginator
    {
        $query = PurchaseOrder::query()->with([
            'supplier',
            'warehouse',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->string('search')->toString(), [
                'po_number',
                'payment_terms',
                'notes',
                'shipping_address',
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'supplier_id' => $request->get('supplier_id'),
            'warehouse_id' => $request->get('warehouse_id'),
            'status' => $request->get('status'),
            'currency' => $request->get('currency'),
            'order_date_from' => $request->get('order_date_from'),
            'order_date_to' => $request->get('order_date_to'),
            'expected_delivery_date_from' => $request->get('expected_delivery_date_from'),
            'expected_delivery_date_to' => $request->get('expected_delivery_date_to'),
            'grand_total_min' => $request->get('grand_total_min'),
            'grand_total_max' => $request->get('grand_total_max'),
        ]);

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = strtolower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
        ];

        $sortBy = $sortMap[$sortBy] ?? $sortBy;

        $this->filterService->applySorting($query, $sortBy, $sortDirection, [
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
        ]);

        return $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1),
        );
    }
}
