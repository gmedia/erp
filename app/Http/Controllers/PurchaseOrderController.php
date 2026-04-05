<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseOrders\ExportPurchaseOrdersAction;
use App\Actions\PurchaseOrders\IndexPurchaseOrdersAction;
use App\Actions\PurchaseOrders\SyncPurchaseOrderItemsAction;
use App\DTOs\PurchaseOrders\UpdatePurchaseOrderData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\PurchaseOrders\ExportPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrders\PurchaseOrderCollection;
use App\Http\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexPurchaseOrderRequest $request, IndexPurchaseOrdersAction $action): JsonResponse
    {
        $purchaseOrders = $action->execute($request);

        return (new PurchaseOrderCollection($purchaseOrders))->response();
    }

    public function store(StorePurchaseOrderRequest $request, SyncPurchaseOrderItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $purchaseOrder = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): PurchaseOrder => PurchaseOrder::create($attributes),
            assignDocumentNumber: function (PurchaseOrder $purchaseOrder): void {
                $this->assignSequentialDocumentNumber($purchaseOrder, 'po_number', 'PO');
            },
            syncItems: function (PurchaseOrder $purchaseOrder, array $items) use ($syncItems): void {
                $syncItems->execute($purchaseOrder, $items);
            },
        );

        $purchaseOrder->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

        return (new PurchaseOrderResource($purchaseOrder))->response()->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        SyncPurchaseOrderItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'confirmed' && $purchaseOrder->approved_at === null) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now()->toIso8601String();
        }

        $dto = UpdatePurchaseOrderData::fromArray($validated);

        DB::transaction(function () use ($purchaseOrder, $dto, $items, $syncItems) {
            $purchaseOrder->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($purchaseOrder, $items);
            }
        });

        $purchaseOrder->load(['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit']);

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->delete();

        return response()->json(null, 204);
    }

    public function export(ExportPurchaseOrderRequest $request, ExportPurchaseOrdersAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
