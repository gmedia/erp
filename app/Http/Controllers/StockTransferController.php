<?php

namespace App\Http\Controllers;

use App\Actions\StockTransfers\ExportStockTransfersAction;
use App\Actions\StockTransfers\IndexStockTransfersAction;
use App\Actions\StockTransfers\SyncStockTransferItemsAction;
use App\DTOs\StockTransfers\UpdateStockTransferData;
use App\Http\Requests\StockTransfers\ExportStockTransferRequest;
use App\Http\Requests\StockTransfers\IndexStockTransferRequest;
use App\Http\Requests\StockTransfers\StoreStockTransferRequest;
use App\Http\Requests\StockTransfers\UpdateStockTransferRequest;
use App\Http\Resources\StockTransfers\StockTransferCollection;
use App\Http\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
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

        $transfer = DB::transaction(function () use ($data, $items, $syncItems) {
            $transfer = StockTransfer::create($data);

            if (empty($transfer->transfer_number)) {
                $transfer->update([
                    'transfer_number' => 'ST-' . now()->format('Y') . '-' . str_pad((string) $transfer->id, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $syncItems->execute($transfer, $items);

            return $transfer;
        });

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

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer, SyncStockTransferItemsAction $syncItems): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $dto = UpdateStockTransferData::fromArray($validated);

        DB::transaction(function () use ($stockTransfer, $dto, $items, $syncItems) {
            $stockTransfer->update($dto->toArray());

            if (is_array($items)) {
                $syncItems->execute($stockTransfer, $items);
            }
        });

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
