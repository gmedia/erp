<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseOrders\ExportPurchaseOrdersAction;
use App\Actions\PurchaseOrders\IndexPurchaseOrdersAction;
use App\Actions\PurchaseOrders\SyncPurchaseOrderItemsAction;
use App\DTOs\PurchaseOrders\UpdatePurchaseOrderData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
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

class PurchaseOrderController extends Controller
{
    use LoadsResourceRelations;
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

        return (new PurchaseOrderResource($this->loadResourceRelations($purchaseOrder)))->response()->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return (new PurchaseOrderResource($this->loadResourceRelations($purchaseOrder)))->response();
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

        $this->updateWithSyncedItems(
            model: $purchaseOrder,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdatePurchaseOrderData::fromArray($attributes)->toArray(),
            syncItems: function (PurchaseOrder $purchaseOrder, array $items) use ($syncItems): void {
                $syncItems->execute($purchaseOrder, $items);
            },
        );

        return (new PurchaseOrderResource($this->loadResourceRelations($purchaseOrder)))->response();
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return $this->destroyModel($purchaseOrder);
    }

    public function export(ExportPurchaseOrderRequest $request, ExportPurchaseOrdersAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['supplier', 'warehouse', 'approver', 'creator', 'items.product', 'items.unit'];
    }
}
