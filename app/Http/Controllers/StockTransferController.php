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
use App\Models\StockTransferItem;
use App\Services\StockPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $stockPosting = app(StockPostingService::class);

        // Issue #356 baris 5-6: update, cek stok, dan posting dalam satu transaksi.
        // Jika stok tidak cukup, seluruh perubahan dibatalkan (422).
        $hasExplicitReceived = is_array($items) && collect($items)->contains(
            fn (array $item): bool => array_key_exists('quantity_received', $item) && $item['quantity_received'] !== null,
        );

        DB::transaction(function () use ($stockTransfer, $validated, $items, $syncItems, $stockPosting, $hasExplicitReceived): void {
            $wasDispatched = in_array($stockTransfer->status, ['in_transit', 'received'], true);
            $wasReceived = $stockTransfer->status === 'received';
            $nextStatus = $validated['status'] ?? $stockTransfer->status;

            // Issue #360 baris 4c: transisi mundur ditolak. Received/in_transit tidak boleh
            // kembali ke tahap sebelum dispatch, karena stok sudah bergerak.
            if ($wasDispatched && in_array($nextStatus, ['draft', 'pending_approval', 'approved'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Transfer yang sudah dikirim tidak bisa kembali ke status ' . $nextStatus . '.'],
                ])->status(422);
            }

            if ($wasReceived && $nextStatus === 'in_transit') {
                throw ValidationException::withMessages([
                    'status' => ['Transfer yang sudah diterima tidak bisa kembali ke in_transit.'],
                ])->status(422);
            }

            // Issue #360 keputusan 3: setelah dispatch, item dan qty kirim terkunci.
            // Hanya quantity_received yang boleh berubah.
            if ($wasDispatched && is_array($items) && $nextStatus !== 'cancelled') {
                $this->assertDispatchedItemsUnchanged($stockTransfer, $items);
            }

            $stockTransfer->update(UpdateStockTransferData::fromArray($validated)->toArray());

            // Issue #360 baris 4: pembatalan transfer yang sudah terposting membalik stok,
            // lewat satu jalur yang sama dengan DELETE (reverseTransfer).
            if ($wasDispatched && $nextStatus === 'cancelled') {
                $stockPosting->reverseTransfer($stockTransfer->refresh());

                return;
            }

            if (is_array($items)) {
                $syncItems->execute($stockTransfer, $items);
            }

            $stockTransfer->refresh()->load('items');

            // Issue #360 baris 2: transisi ke received tanpa quantity_received per baris
            // (tidak dikirim) = diterima penuh. Nilai eksplisit (termasuk 0) tetap dihormati.
            if (! $wasReceived && $stockTransfer->status === 'received' && ! $hasExplicitReceived) {
                foreach ($stockTransfer->items as $item) {
                    $item->forceFill(['quantity_received' => $item->quantity])->saveQuietly();
                }
                $stockTransfer->refresh()->load('items');
            }

            // Issue #360 baris 1: stok hanya dicek saat qty BARU keluar (transisi ke
            // dispatch). Setelah dispatch, stok sudah keluar; mengecek ulang = 422 palsu.
            if (! $wasDispatched && in_array($stockTransfer->status, ['in_transit', 'received'], true)) {
                $stockPosting->assertNoNegativeStock(
                    $stockTransfer->items
                        ->map(fn ($item): array => [
                            (int) $item->product_id,
                            (int) $stockTransfer->from_warehouse_id,
                            (float) $item->quantity,
                        ])
                        ->all(),
                );
            }

            $stockPosting->postStockTransferDispatch($stockTransfer);
            $stockPosting->postStockTransferReceive($stockTransfer);
        });

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.unit']);

        return (new StockTransferResource($stockTransfer))->response();
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        // Issue #356 baris 4: transfer yang sudah dikirim dibatalkan lewat movement
        // pembalik (histori tidak dihapus), dalam transaksi yang sama.
        DB::transaction(function () use ($stockTransfer): void {
            $wasPosted = in_array($stockTransfer->status, ['in_transit', 'received'], true);

            $stockTransfer->update(['status' => 'cancelled']);

            if ($wasPosted) {
                app(StockPostingService::class)->reverseTransfer($stockTransfer->refresh());
            }
        });

        return response()->json(null, 204);
    }

    public function export(ExportStockTransferRequest $request, ExportStockTransfersAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function assertDispatchedItemsUnchanged(StockTransfer $stockTransfer, array $items): void
    {
        $current = StockTransferItem::query()
            ->where('stock_transfer_id', $stockTransfer->getKey())
            ->get(['product_id', 'unit_id', 'quantity'])
            ->mapWithKeys(fn (StockTransferItem $item): array => [
                (int) $item->product_id => [(int) $item->unit_id, (float) $item->quantity],
            ])
            ->all();

        $incoming = collect($items)
            ->mapWithKeys(fn (array $item): array => [
                (int) $item['product_id'] => [(int) $item['unit_id'], (float) $item['quantity']],
            ])
            ->all();

        ksort($current);
        ksort($incoming);

        if ($current !== $incoming) {
            throw ValidationException::withMessages([
                'items' => ['Item dan qty kirim tidak bisa diubah setelah transfer dikirim; hanya qty diterima yang bisa diubah.'],
            ])->status(422);
        }
    }
}
