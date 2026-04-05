<?php

namespace App\Http\Controllers;

use App\Actions\InventoryStocktakes\ExportInventoryStocktakesAction;
use App\Actions\InventoryStocktakes\IndexInventoryStocktakesAction;
use App\Actions\InventoryStocktakes\SyncInventoryStocktakeItemsAction;
use App\DTOs\InventoryStocktakes\UpdateInventoryStocktakeData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\InventoryStocktakes\ExportInventoryStocktakeRequest;
use App\Http\Requests\InventoryStocktakes\IndexInventoryStocktakeRequest;
use App\Http\Requests\InventoryStocktakes\StoreInventoryStocktakeRequest;
use App\Http\Requests\InventoryStocktakes\UpdateInventoryStocktakeRequest;
use App\Http\Resources\InventoryStocktakes\InventoryStocktakeCollection;
use App\Http\Resources\InventoryStocktakes\InventoryStocktakeResource;
use App\Models\InventoryStocktake;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InventoryStocktakeController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexInventoryStocktakeRequest $request, IndexInventoryStocktakesAction $action): JsonResponse
    {
        $stocktakes = $action->execute($request);

        return (new InventoryStocktakeCollection($stocktakes))->response();
    }

    public function store(
        StoreInventoryStocktakeRequest $request,
        SyncInventoryStocktakeItemsAction $syncItems
    ): JsonResponse {
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);

        $data['created_by'] = Auth::id();

        $stocktake = $this->storeWithSyncedItems(
            attributes: $data,
            items: $items,
            creator: static fn (array $attributes): InventoryStocktake => InventoryStocktake::create($attributes),
            assignDocumentNumber: function (InventoryStocktake $stocktake): void {
                $this->assignSequentialDocumentNumber($stocktake, 'stocktake_number', 'SO');
            },
            syncItems: function (InventoryStocktake $stocktake, array $items) use ($syncItems): void {
                $syncItems->execute($stocktake, $items);
            },
        );

        $stocktake->load([
            'warehouse',
            'productCategory',
            'createdBy',
            'completedBy',
            'items.product',
            'items.unit',
            'items.countedBy',
        ]);

        return (new InventoryStocktakeResource($stocktake))
            ->response()
            ->setStatusCode(201);
    }

    public function show(InventoryStocktake $inventoryStocktake): JsonResponse
    {
        $inventoryStocktake->load([
            'warehouse',
            'productCategory',
            'createdBy',
            'completedBy',
            'items.product',
            'items.unit',
            'items.countedBy',
        ]);

        return (new InventoryStocktakeResource($inventoryStocktake))->response();
    }

    public function update(
        UpdateInventoryStocktakeRequest $request,
        InventoryStocktake $inventoryStocktake,
        SyncInventoryStocktakeItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'completed' && $inventoryStocktake->completed_at === null) {
            $validated['completed_by'] = Auth::id();
            $validated['completed_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $inventoryStocktake,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateInventoryStocktakeData::fromArray($attributes)->toArray(),
            syncItems: function (InventoryStocktake $inventoryStocktake, array $items) use ($syncItems): void {
                $syncItems->execute($inventoryStocktake, $items);
            },
        );

        $inventoryStocktake->load(['warehouse', 'productCategory', 'items.product', 'items.unit', 'items.countedBy']);

        return (new InventoryStocktakeResource($inventoryStocktake))->response();
    }

    public function destroy(InventoryStocktake $inventoryStocktake): JsonResponse
    {
        $inventoryStocktake->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }

    public function export(
        ExportInventoryStocktakeRequest $request,
        ExportInventoryStocktakesAction $action
    ): JsonResponse {
        return $action->execute($request);
    }
}
