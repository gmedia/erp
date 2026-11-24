<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostStockTransferJournalAction;
use App\Actions\StockTransfers\ExportStockTransfersAction;
use App\Actions\StockTransfers\IndexStockTransfersAction;
use App\Actions\StockTransfers\SyncStockTransferItemsAction;
use App\DTOs\StockTransfers\UpdateStockTransferData;
use App\Http\Controllers\Concerns\DeletesOnlyDraftDocuments;
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
    use DeletesOnlyDraftDocuments;
    use StoresItemsInTransaction;

    /**
     * Tabel transisi status yang sah. Transisi yang tidak tercantum ditolak (422).
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['draft', 'pending_approval', 'approved', 'in_transit', 'received', 'cancelled'],
        'pending_approval' => ['pending_approval', 'draft', 'approved', 'cancelled'],
        'approved' => ['approved', 'draft', 'pending_approval', 'in_transit', 'cancelled'],
        'in_transit' => ['in_transit', 'received', 'cancelled'],
        'received' => ['received', 'cancelled'],
        'cancelled' => ['cancelled'],
    ];

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

        // Create langsung berstatus received: jurnal selisih ikut diposting (Issue #357).
        app(PostStockTransferJournalAction::class)->execute($transfer->refresh());

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
            fn (array $item): bool => array_key_exists('quantity_received', $item)
                && $item['quantity_received'] !== null,
        );

        DB::transaction(function () use (
            $stockTransfer,
            $validated,
            $items,
            $syncItems,
            $stockPosting,
            $hasExplicitReceived,
        ): void {
            $wasDispatched = in_array($stockTransfer->status, ['in_transit', 'received'], true);
            $wasReceived = $stockTransfer->status === 'received';
            $wasCancelled = $stockTransfer->status === 'cancelled';
            $nextStatus = $validated['status'] ?? $stockTransfer->status;

            // Issue #360 review baris 4: cancelled adalah status akhir; edit item pun ditolak.
            if ($wasCancelled && ($nextStatus !== 'cancelled' || is_array($items))) {
                throw ValidationException::withMessages([
                    'status' => [__('stock_transfer.cancelled_final')],
                ])->status(422);
            }

            $this->assertTransitionAllowed($stockTransfer->status, $nextStatus);

            // Issue #360 keputusan 3: setelah dispatch, item dan qty kirim terkunci.
            // Hanya quantity_received yang boleh berubah.
            if ($wasDispatched && is_array($items) && $nextStatus !== 'cancelled') {
                $this->assertDispatchedItemsUnchanged($stockTransfer, $items);
            }

            // Issue #436: jejak approved/shipped/received diisi server saat transisi, bukan payload.
            $validated = array_merge($validated, $this->workflowStampsFor($stockTransfer, $nextStatus));

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

            // Issue #360 review baris 3: qty diterima tidak boleh melebihi qty yang dikirim.
            if ($stockTransfer->status === 'received') {
                $this->assertReceivedNotExceedsQuantity($stockTransfer);
            }

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
            app(PostStockTransferJournalAction::class)->execute($stockTransfer->refresh());
        });

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.unit']);

        return (new StockTransferResource($stockTransfer))->response();
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        return $this->destroyDraftDocument($stockTransfer);
    }

    public function export(ExportStockTransferRequest $request, ExportStockTransfersAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * Stempel jejak alur (Issue #436). Hanya diisi server saat kolom masih kosong.
     *
     * @return array<string, mixed>
     */
    private function workflowStampsFor(StockTransfer $stockTransfer, string $nextStatus): array
    {
        $now = now()->toIso8601String();
        $userId = Auth::id();
        $stamps = [];

        if ($nextStatus === 'approved' && $stockTransfer->approved_at === null) {
            $stamps += ['approved_by' => $userId, 'approved_at' => $now];
        }

        if ($nextStatus === 'in_transit' && $stockTransfer->shipped_at === null) {
            $stamps += ['shipped_by' => $userId, 'shipped_at' => $now];
        }

        if ($nextStatus === 'received' && $stockTransfer->received_at === null) {
            $stamps += ['received_by' => $userId, 'received_at' => $now];
        }

        return $stamps;
    }

    private function assertTransitionAllowed(string $from, string $to): void
    {
        if (in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true)) {
            return;
        }

        $message = match (true) {
            $from === 'cancelled' => __('stock_transfer.cancelled_final'),
            $from === 'received' && $to === 'in_transit' => __('stock_transfer.already_received'),
            in_array($from, ['in_transit', 'received'], true) => __(
                'stock_transfer.already_dispatched',
                ['status' => $to],
            ),
            default => __('stock_transfer.cancelled_final'),
        };

        throw ValidationException::withMessages(['status' => [$message]])->status(422);
    }

    private function assertReceivedNotExceedsQuantity(StockTransfer $stockTransfer): void
    {
        $exceeds = $stockTransfer->items->contains(
            fn (StockTransferItem $item): bool => $item->quantity_received !== null
                && (float) $item->quantity_received > (float) $item->quantity,
        );

        if ($exceeds) {
            throw ValidationException::withMessages([
                'items' => [__('stock_transfer.qty_received_exceeds')],
            ])->status(422);
        }
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
                'items' => [__('stock_transfer.items_locked')],
            ])->status(422);
        }
    }
}
