<?php

namespace App\Http\Controllers;

use App\Actions\GoodsReceipts\ExportGoodsReceiptsAction;
use App\Actions\GoodsReceipts\IndexGoodsReceiptsAction;
use App\Actions\GoodsReceipts\SyncGoodsReceiptItemsAction;
use App\DTOs\GoodsReceipts\UpdateGoodsReceiptData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\GoodsReceipts\ExportGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\StoreGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\UpdateGoodsReceiptRequest;
use App\Http\Resources\GoodsReceipts\GoodsReceiptCollection;
use App\Http\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexGoodsReceiptRequest $request, IndexGoodsReceiptsAction $action): JsonResponse
    {
        $goodsReceipts = $action->execute($request);

        return (new GoodsReceiptCollection($goodsReceipts))->response();
    }

    public function store(StoreGoodsReceiptRequest $request, SyncGoodsReceiptItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        if (($validated['status'] ?? null) === 'confirmed') {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $goodsReceipt = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): GoodsReceipt => GoodsReceipt::create($attributes),
            assignDocumentNumber: function (GoodsReceipt $goodsReceipt): void {
                $this->assignSequentialDocumentNumber($goodsReceipt, 'gr_number', 'GR');
            },
            syncItems: function (GoodsReceipt $goodsReceipt, array $items) use ($syncItems): void {
                $syncItems->execute($goodsReceipt, $items);
            },
        );

        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response()->setStatusCode(201);
    }

    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response();
    }

    public function update(
        UpdateGoodsReceiptRequest $request,
        GoodsReceipt $goodsReceipt,
        SyncGoodsReceiptItemsAction $syncItems
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        if (($validated['status'] ?? null) === 'confirmed' && $goodsReceipt->confirmed_at === null) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        $this->updateWithSyncedItems(
            model: $goodsReceipt,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateGoodsReceiptData::fromArray($attributes)->toArray(),
            syncItems: function (GoodsReceipt $goodsReceipt, array $items) use ($syncItems): void {
                $syncItems->execute($goodsReceipt, $items);
            },
        );

        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response();
    }

    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        return $this->destroyModel($goodsReceipt);
    }

    public function export(ExportGoodsReceiptRequest $request, ExportGoodsReceiptsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit'];
    }
}
