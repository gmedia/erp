<?php

namespace App\Http\Controllers;

use App\Actions\StockTransfers\ExportStockTransfersAction;
use App\Actions\StockTransfers\IndexStockTransfersAction;
use App\Actions\StockTransfers\SyncStockTransferItemsAction;
use App\DTOs\StockTransfers\UpdateStockTransferData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\StockTransfers\ExportStockTransferRequest;
use App\Http\Requests\StockTransfers\IndexStockTransferRequest;
use App\Http\Requests\StockTransfers\StoreStockTransferRequest;
use App\Http\Requests\StockTransfers\UpdateStockTransferRequest;
use App\Http\Resources\StockTransfers\StockTransferCollection;
use App\Http\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexStockTransferRequest $request, IndexStockTransfersAction $action): JsonResponse
    {
        $transfers = $action->execute($request);

        return (new StockTransferCollection($transfers))->response();
    }

    public function store(StoreStockTransferRequest $request, SyncStockTransferItemsAction $syncItems): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $data['created_by'] = Auth::id();

        $transfer = $this->storeWithSyncedItems(
            attributes: $data,
            items: $items,
            creator: static fn (array $attributes): StockTransfer => StockTransfer::create($attributes),
            assignDocumentNumber: function (StockTransfer $transfer): void {
                $this->assignSequentialDocumentNumber($transfer, 'transfer_number', 'ST');
            },
            syncItems: function (StockTransfer $transfer, array $items) use ($syncItems): void {
                $syncItems->execute($transfer, $items);
            },
        );

        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.unit']);

        return (new StockTransferResource($transfer))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->load([
            'fromWarehouse',
            'toWarehouse',
            'requestedBy',
            'approvedBy',
            'shippedBy',
            'receivedBy',
            'createdBy',
            'items.product',
            'items.unit',
        ]);

        return (new StockTransferResource($stockTransfer))->response();
    }

    public function update(
        UpdateStockTransferRequest $request,
        StockTransfer $stockTransfer,
        SyncStockTransferItemsAction $syncItems,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $this->updateWithSyncedItems(
            model: $stockTransfer,
            attributes: $validated,
            items: $items,
            payloadResolver: static fn (array $attributes): array => UpdateStockTransferData::fromArray($attributes)->toArray(),
            syncItems: function (StockTransfer $stockTransfer, array $items) use ($syncItems): void {
                $syncItems->execute($stockTransfer, $items);
            },
        );

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.unit']);

        return (new StockTransferResource($stockTransfer))->response();
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }

    public function export(ExportStockTransferRequest $request, ExportStockTransfersAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
