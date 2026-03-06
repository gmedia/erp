<?php

namespace App\Actions\SupplierReturns;

use App\Domain\SupplierReturns\SupplierReturnFilterService;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use App\Models\SupplierReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSupplierReturnsAction
{
    public function __construct(
        private SupplierReturnFilterService $filterService
    ) {
    }

    public function execute(IndexSupplierReturnRequest $request): LengthAwarePaginator
    {
        $query = SupplierReturn::query()->with([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->string('search')->toString(), [
                'return_number',
                'notes',
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'purchase_order_id' => $request->get('purchase_order_id'),
            'goods_receipt_id' => $request->get('goods_receipt_id'),
            'supplier_id' => $request->get('supplier_id'),
            'warehouse_id' => $request->get('warehouse_id'),
            'reason' => $request->get('reason'),
            'status' => $request->get('status'),
            'return_date_from' => $request->get('return_date_from'),
            'return_date_to' => $request->get('return_date_to'),
        ]);

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = strtolower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'purchase_order' => 'purchase_order_id',
            'goods_receipt' => 'goods_receipt_id',
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
        ];

        $sortBy = $sortMap[$sortBy] ?? $sortBy;

        $this->filterService->applySorting($query, $sortBy, $sortDirection, [
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
        ]);

        return $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1),
        );
    }
}
