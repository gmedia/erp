<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseRequests\ExportPurchaseRequestsAction;
use App\Actions\PurchaseRequests\IndexPurchaseRequestsAction;
use App\Actions\PurchaseRequests\SyncPurchaseRequestItemsAction;
use App\DTOs\PurchaseRequests\UpdatePurchaseRequestData;
use App\Http\Requests\PurchaseRequests\ExportPurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\UpdatePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequests\PurchaseRequestCollection;
use App\Http\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
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

        $purchaseRequest = DB::transaction(function () use ($validated, $items, $syncItems) {
            $purchaseRequest = PurchaseRequest::create($validated);

            if (empty($purchaseRequest->pr_number)) {
                $purchaseRequest->update([
                    'pr_number' => 'PR-'
                        . now()->format('Y')
                        . '-'
                        . str_pad((string) $purchaseRequest->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $syncItems->execute($purchaseRequest, $items);

            return $purchaseRequest;
        });

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

        $dto = UpdatePurchaseRequestData::fromArray($validated);

        DB::transaction(function () use ($purchaseRequest, $dto, $items, $syncItems) {
            $purchaseRequest->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($purchaseRequest, $items);
            }
        });

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
        $purchaseRequest->delete();

        return response()->json(null, 204);
    }

    public function export(ExportPurchaseRequestRequest $request, ExportPurchaseRequestsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
