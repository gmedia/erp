<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseRequests\ExportPurchaseRequestsAction;
use App\Actions\PurchaseRequests\IndexPurchaseRequestsAction;
use App\Actions\PurchaseRequests\SyncPurchaseRequestItemsAction;
use App\DTOs\PurchaseRequests\UpdatePurchaseRequestData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\PurchaseRequests\ExportPurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequests\PurchaseRequestCollection;
use App\Http\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexPurchaseRequestRequest $request, IndexPurchaseRequestsAction $action): JsonResponse
    {
        $purchaseRequests = $action->execute($request);

        return (new PurchaseRequestCollection($purchaseRequests))->response();
    }

    public function store(StorePurchaseRequestRequest $request, SyncPurchaseRequestItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $purchaseRequest = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): PurchaseRequest => PurchaseRequest::create($attributes),
            assignDocumentNumber: function (PurchaseRequest $purchaseRequest): void {
                $this->assignSequentialDocumentNumber($purchaseRequest, 'pr_number', 'PR');
            },
            syncItems: function (PurchaseRequest $purchaseRequest, array $items) use ($syncItems): void {
                $syncItems->execute($purchaseRequest, $items);
            },
        );

        $purchaseRequest->load([
            'branch',
            'department',
            'requester',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new PurchaseRequestResource($purchaseRequest))->response()->setStatusCode(201);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest->load([
            'branch',
            'department',
            'requester',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new PurchaseRequestResource($purchaseRequest))->response();
    }

    public function update(
        UpdatePurchaseRequestRequest $request,
        PurchaseRequest $purchaseRequest,
        SyncPurchaseRequestItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'approved' && $purchaseRequest->approved_at === null) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $purchaseRequest,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdatePurchaseRequestData::fromArray($attributes)->toArray(),
            syncItems: function (PurchaseRequest $purchaseRequest, array $items) use ($syncItems): void {
                $syncItems->execute($purchaseRequest, $items);
            },
        );

        $purchaseRequest->load([
            'branch',
            'department',
            'requester',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new PurchaseRequestResource($purchaseRequest))->response();
    }

    public function destroy(PurchaseRequest $purchaseRequest): JsonResponse
    {
        return $this->destroyModel($purchaseRequest);
    }

    public function export(ExportPurchaseRequestRequest $request, ExportPurchaseRequestsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
