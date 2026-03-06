<?php

namespace App\Http\Controllers;

use App\Actions\GoodsReceipts\ExportGoodsReceiptsAction;
use App\Actions\GoodsReceipts\IndexGoodsReceiptsAction;
use App\Actions\GoodsReceipts\SyncGoodsReceiptItemsAction;
use App\DTOs\GoodsReceipts\UpdateGoodsReceiptData;
use App\Http\Requests\GoodsReceipts\ExportGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\StoreGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipts\UpdateGoodsReceiptRequest;
use App\Http\Resources\GoodsReceipts\GoodsReceiptCollection;
use App\Http\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
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

        $goodsReceipt = DB::transaction(function () use ($validated, $items, $syncItems) {
            $goodsReceipt = GoodsReceipt::create($validated);

            if (empty($goodsReceipt->gr_number)) {
                $goodsReceipt->update([
                    'gr_number' => 'GR-' . now()->format('Y') . '-' . str_pad((string) $goodsReceipt->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $syncItems->execute($goodsReceipt, $items);

            return $goodsReceipt;
        });

        $goodsReceipt->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit']);

        return (new GoodsReceiptResource($goodsReceipt))->response()->setStatusCode(201);
    }

    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit']);

        return (new GoodsReceiptResource($goodsReceipt))->response();
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

        $dto = UpdateGoodsReceiptData::fromArray($validated);

        DB::transaction(function () use ($goodsReceipt, $dto, $items, $syncItems) {
            $goodsReceipt->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($goodsReceipt, $items);
            }
        });

        $goodsReceipt->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit']);

        return (new GoodsReceiptResource($goodsReceipt))->response();
    }

    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->delete();

        return response()->json(null, 204);
    }

    public function export(ExportGoodsReceiptRequest $request, ExportGoodsReceiptsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
