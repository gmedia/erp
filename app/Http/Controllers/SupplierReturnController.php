<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Actions\AccountingPosting\ReverseSupplierReturnJournalAction;
use App\Actions\SupplierReturns\EnsureGoodsReceiptItemNotOverReturnedAction;
use App\Actions\SupplierReturns\ExportSupplierReturnsAction;
use App\Actions\SupplierReturns\IndexSupplierReturnsAction;
use App\Actions\SupplierReturns\PostSupplierReturnMovementsAction;
use App\Actions\SupplierReturns\SyncSupplierReturnItemsAction;
use App\DTOs\SupplierReturns\UpdateSupplierReturnData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\SupplierReturns\ExportSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\StoreSupplierReturnRequest;
use App\Http\Requests\SupplierReturns\UpdateSupplierReturnRequest;
use App\Http\Resources\SupplierReturns\SupplierReturnCollection;
use App\Http\Resources\SupplierReturns\SupplierReturnResource;
use App\Models\SupplierReturn;
use App\Services\StockPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierReturnController extends Controller
{
    use StoresItemsInTransaction;

    private const ALLOWED_TRANSITIONS = [
        'draft' => ['draft', 'confirmed', 'cancelled'],
        'confirmed' => ['confirmed', 'cancelled'],
        'cancelled' => [],
    ];

    public function index(IndexSupplierReturnRequest $request, IndexSupplierReturnsAction $action): JsonResponse
    {
        $supplierReturns = $action->execute($request);

        return (new SupplierReturnCollection($supplierReturns))->response();
    }

    public function store(
        StoreSupplierReturnRequest $request,
        SyncSupplierReturnItemsAction $syncItems,
        PostSupplierReturnJournalAction $postJournal,
        PostSupplierReturnMovementsAction $postMovements,
        StockPostingService $stockPosting,
        EnsureGoodsReceiptItemNotOverReturnedAction $overReturn,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $validated['created_by'] = Auth::id();

        $isConfirmedOnCreate = ($validated['status'] ?? null) === 'confirmed';

        if ($isConfirmedOnCreate) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        // Issue #367: retur yang dibuat langsung confirmed harus membentuk stok dan jurnal
        // dalam transaksi yang sama. Kegagalan posting ikut membatalkan dokumen.
        $supplierReturn = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $items,
            creator: static fn (array $attributes): SupplierReturn => SupplierReturn::create($attributes),
            assignDocumentNumber: function (SupplierReturn $supplierReturn): void {
                $this->assignSequentialDocumentNumber($supplierReturn, 'return_number', 'SR');
            },
            syncItems: function (SupplierReturn $supplierReturn, array $items) use (
                $syncItems,
                $overReturn,
                $isConfirmedOnCreate,
            ): void {
                // Butir 4: draft tidak mengikat kuota; dicek ulang saat konfirmasi.
                if ($isConfirmedOnCreate) {
                    $overReturn->execute($items);
                }

                $syncItems->execute($supplierReturn, $items);
            },
            afterCommit: $isConfirmedOnCreate
                ? function (SupplierReturn $supplierReturn) use ($postJournal, $postMovements, $stockPosting): void {
                    $this->postConfirmedReturn($supplierReturn, $postJournal, $postMovements, $stockPosting);
                }
            : null,
        );

        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new SupplierReturnResource($supplierReturn))->response()->setStatusCode(201);
    }

    public function show(SupplierReturn $supplierReturn): JsonResponse
    {
        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new SupplierReturnResource($supplierReturn))->response();
    }

    public function update(
        UpdateSupplierReturnRequest $request,
        SupplierReturn $supplierReturn,
        SyncSupplierReturnItemsAction $syncItems,
        PostSupplierReturnJournalAction $postJournal,
        PostSupplierReturnMovementsAction $postMovements,
        StockPostingService $stockPosting,
        ReverseSupplierReturnJournalAction $reverseJournal,
        EnsureGoodsReceiptItemNotOverReturnedAction $overReturn,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $requestedStatus = $validated['status'] ?? null;
        $currentStatus = $supplierReturn->status;
        $nextStatus = $requestedStatus ?? $currentStatus;

        $this->assertTransitionAllowed($currentStatus, $nextStatus);

        if ($currentStatus !== 'draft' && is_array($items) && $nextStatus !== 'cancelled') {
            throw ValidationException::withMessages([
                'items' => [__('supplier_return.items_locked')],
            ])->status(422);
        }

        if ($currentStatus === 'confirmed' && $nextStatus === 'cancelled') {
            // Issue #362: retur confirmed dibatalkan lewat pembalik (stok + jurnal), histori tetap.
            // Baris retur dikunci agar dua permintaan batal bersamaan tidak memposting dua kali.
            DB::transaction(function () use ($supplierReturn, $validated, $stockPosting, $reverseJournal): void {
                $locked = SupplierReturn::query()->whereKey($supplierReturn->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'confirmed') {
                    throw ValidationException::withMessages([
                        'status' => [__('supplier_return.already_cancelled')],
                    ])->status(422);
                }

                $supplierReturn->update(UpdateSupplierReturnData::fromArray(
                    array_merge($validated, ['notes' => $this->cancellationNotes($supplierReturn, $validated)]),
                )->toArray());
                $stockPosting->reverseDocument(SupplierReturn::class, $supplierReturn->id, 'supplier_return');
                $reverseJournal->execute($supplierReturn->refresh());
            });

            $supplierReturn->load([
                'purchaseOrder',
                'goodsReceipt',
                'supplier',
                'warehouse',
                'creator',
                'items.product',
                'items.unit',
            ]);

            return (new SupplierReturnResource($supplierReturn))->response();
        }

        $isNewlyConfirmed = $nextStatus === 'confirmed' && $supplierReturn->confirmed_at === null;

        if ($isNewlyConfirmed) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        // Butir 1 review: konfirmasi yang hanya membawa status dicek terhadap item tersimpan,
        // di dalam transaksi dengan lock baris GR (sama seperti jalur items).
        $confirmsWithoutItems = $isNewlyConfirmed && $items === null;

        DB::transaction(function () use (
            $supplierReturn,
            $validated,
            $items,
            $nextStatus,
            $confirmsWithoutItems,
            $isNewlyConfirmed,
            $overReturn,
            $syncItems,
            $postJournal,
            $postMovements,
            $stockPosting,
        ): void {
            if ($confirmsWithoutItems) {
                $overReturn->execute($supplierReturn->items()->get()->toArray(), $supplierReturn->id);
            }

            $this->updateWithSyncedItems(
                model: $supplierReturn,
                attributes: $validated,
                items: $items,
                payloadResolver: static function (array $attributes): array {
                    return UpdateSupplierReturnData::fromArray($attributes)->toArray();
                },
                syncItems: function (SupplierReturn $supplierReturn, array $items) use (
                    $syncItems,
                    $overReturn,
                    $nextStatus,
                ): void {
                    if ($nextStatus === 'confirmed') {
                        $overReturn->execute($items, $supplierReturn->id);
                    }

                    $syncItems->execute($supplierReturn, $items);
                },
                afterCommit: $isNewlyConfirmed
                    ? function (SupplierReturn $supplierReturn) use (
                        $postJournal,
                        $postMovements,
                        $stockPosting,
                    ): void {
                        $this->postConfirmedReturn($supplierReturn, $postJournal, $postMovements, $stockPosting);
                    }
                : null,
            );
        });

        $supplierReturn->load([
            'purchaseOrder',
            'goodsReceipt',
            'supplier',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return (new SupplierReturnResource($supplierReturn))->response();
    }

    public function destroy(SupplierReturn $supplierReturn): JsonResponse
    {
        if ($supplierReturn->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => [__('supplier_return.delete_not_draft')],
            ])->status(422);
        }

        return $this->destroyModel($supplierReturn);
    }

    public function export(ExportSupplierReturnRequest $request, ExportSupplierReturnsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * @return array<int, array{0:int,1:int,2:float}>
     */
    private function supplierReturnOutflows(SupplierReturn $supplierReturn): array
    {
        return $supplierReturn->load('items')->items
            ->map(fn ($item): array => [
                (int) $item->product_id,
                (int) $supplierReturn->warehouse_id,
                (float) $item->quantity_returned,
            ])
            ->all();
    }

    private function cancellationNotes(SupplierReturn $supplierReturn, array $validated): string
    {
        $reason = $validated['notes'] ?? $supplierReturn->notes;
        $stamp = sprintf(
            '[Cancelled by user %s at %s]',
            Auth::user()->name ?? '-',
            now()->toIso8601String(),
        );

        return trim(($reason ? $reason . "\n" : '') . $stamp);
    }

    private function assertTransitionAllowed(string $from, string $to): void
    {
        if (in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true)) {
            return;
        }

        $message = $from === 'cancelled'
            ? __('supplier_return.already_cancelled')
            : __('supplier_return.invalid_transition');

        throw ValidationException::withMessages(['status' => [$message]])->status(422);
    }

    /**
     * Stok dan jurnal retur confirmed, dipakai create dan update dalam transaksi yang sama.
     */
    private function postConfirmedReturn(
        SupplierReturn $supplierReturn,
        PostSupplierReturnJournalAction $postJournal,
        PostSupplierReturnMovementsAction $postMovements,
        StockPostingService $stockPosting,
    ): void {
        $outflows = $this->supplierReturnOutflows($supplierReturn);
        $stockPosting->assertNoNegativeStock($outflows);
        $postMovements->execute($supplierReturn->refresh());

        // Review putaran 3 butir 2: retur bertanggal mundur memeriksa saldo berjalan sejak tanggal
        // retur, bukan hanya saldo akhir. Dicek setelah movement terposting; 422 membatalkan transaksi.
        // Retur hari ini tetap dijaga oleh assertNoNegativeStock di atas.
        $returnDate = $supplierReturn->return_date->copy()->startOfDay();

        if ($returnDate->lt(now()->startOfDay())) {
            $stockPosting->assertNoNegativeStockFrom($outflows, $returnDate);
        }
        // Jurnal gagal (COA/periode) melempar ValidationException: seluruh transaksi
        // dibatalkan, sehingga dokumen, stok, dan status tidak berubah (Issue #416).
        $postJournal->execute($supplierReturn->refresh());
    }
}
