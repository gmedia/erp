<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\StockAdjustments\ExportStockAdjustmentsAction;
use App\Actions\StockAdjustments\IndexStockAdjustmentsAction;
use App\Actions\StockAdjustments\PostStockAdjustmentMovementsAction;
use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\DTOs\StockAdjustments\UpdateStockAdjustmentData;
use App\Http\Controllers\Concerns\CancelsPostedDocuments;
use App\Http\Controllers\Concerns\DeletesOnlyDraftDocuments;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\StoreStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentRequest;
use App\Http\Resources\StockAdjustments\StockAdjustmentCollection;
use App\Http\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\StockPostingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    use CancelsPostedDocuments;
    use DeletesOnlyDraftDocuments;
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexStockAdjustmentRequest $request, IndexStockAdjustmentsAction $action): JsonResponse
    {
        $adjustments = $action->execute($request);

        return (new StockAdjustmentCollection($adjustments))->response();
    }

    public function store(
        StoreStockAdjustmentRequest $request,
        SyncStockAdjustmentItemsAction $syncItems,
        PostStockAdjustmentJournalAction $postJournal,
        PostStockAdjustmentMovementsAction $postMovements,
        StockPostingService $stockPosting,
    ): JsonResponse {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $data['created_by'] = Auth::id();

        $isApprovedOnCreate = ($data['status'] ?? null) === 'approved';

        if ($isApprovedOnCreate) {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now()->toIso8601String();
        }

        // Issue #416: jurnal dan movement stok diposting dalam transaksi yang sama.
        // Jika jurnal gagal (COA/periode), ValidationException membatalkan seluruh
        // transaksi: dokumen tidak tersimpan dan stok tidak berubah (422).
        $adjustment = $this->storeWithSyncedItems(
            attributes: $data,
            items: $items,
            creator: static fn (array $attributes): StockAdjustment => StockAdjustment::create($attributes),
            assignDocumentNumber: function (StockAdjustment $adjustment): void {
                $this->assignSequentialDocumentNumber($adjustment, 'adjustment_number', 'SA');
            },
            syncItems: function (StockAdjustment $adjustment, array $items) use ($syncItems): void {
                $syncItems->execute($adjustment, $items);
            },
            afterCommit: $isApprovedOnCreate
                ? function (StockAdjustment $adjustment) use ($postJournal, $postMovements, $stockPosting): void {
                    $stockPosting->assertNoNegativeStock($this->stockAdjustmentOutflows($adjustment->refresh()));
                    $postJournal->execute($adjustment->refresh());
                    $postMovements->execute($adjustment->refresh());
                    $stockPosting->assertNoNegativeStockFrom(
                        $this->stockAdjustmentOutflows($adjustment->refresh()),
                        $adjustment->adjustment_date->copy()->startOfDay(),
                    );
                }
            : null,
        );

        $adjustment->load(['warehouse', 'inventoryStocktake', 'items.product', 'items.unit']);

        return (new StockAdjustmentResource($adjustment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->load([
            'warehouse',
            'inventoryStocktake',
            'journalEntry',
            'approvedBy',
            'createdBy',
            'items.product',
            'items.unit',
        ]);

        return (new StockAdjustmentResource($stockAdjustment))->response();
    }

    public function update(
        UpdateStockAdjustmentRequest $request,
        StockAdjustment $stockAdjustment,
        SyncStockAdjustmentItemsAction $syncItems,
        PostStockAdjustmentJournalAction $postJournal,
        PostStockAdjustmentMovementsAction $postMovements,
        ReverseDocumentJournalAction $reverseJournal,
        StockPostingService $stockPosting,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        // Issue #375 (review #426 butir 2 & 7): pembatalan dokumen approved membalik stok dan,
        // bila ada, jurnal dalam satu transaksi. Data lama approved tanpa jurnal tetap dibatalkan.
        // Draft/pending_approval belum terposting: dibatalkan tanpa pembalik.
        $isPostedCancel = ($validated['status'] ?? null) === 'cancelled'
            && $stockAdjustment->status === 'approved';

        if ($isPostedCancel) {
            $this->cancelPostedDocument(
                document: $stockAdjustment,
                attributes: $validated,
                blockers: [],
                reference: (string) $stockAdjustment->adjustment_number,
                label: 'Stock Adjustment',
                reverseJournal: $reverseJournal,
                release: static function (Model $adjustment) use ($stockPosting): void {
                    $stockPosting->reverseDocument(StockAdjustment::class, (int) $adjustment->getKey(), 'adjustment');
                },
            );

            return (new StockAdjustmentResource($this->loadResourceRelations($stockAdjustment)))->response();
        }

        $isNewlyApproved = ($validated['status'] ?? null) === 'approved' && $stockAdjustment->approved_at === null;
        $isEditingApproved = $stockAdjustment->status === 'approved' && ! $isNewlyApproved && is_array($items);

        if ($isNewlyApproved) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now()->toIso8601String();
        }

        if ($isEditingApproved) {
            // Issue #372: edit adjustment approved membalik movement asli (bertanggal
            // moved_at asli), lalu item baru diposting ulang, dalam satu transaksi.
            $this->updateApprovedAdjustment($stockAdjustment, $validated, $items, $syncItems, $stockPosting);

            $stockAdjustment->load(['warehouse', 'inventoryStocktake', 'journalEntry', 'items.product', 'items.unit']);

            return (new StockAdjustmentResource($stockAdjustment))->response();
        }

        $this->updateWithSyncedItems(
            model: $stockAdjustment,
            attributes: $validated,
            items: $items,
            payloadResolver: static function (array $attributes): array {
                return UpdateStockAdjustmentData::fromArray($attributes)->toArray();
            },
            syncItems: function (StockAdjustment $stockAdjustment, array $items) use ($syncItems): void {
                $syncItems->execute($stockAdjustment, $items);
            },
            // Issue #416: jurnal gagal (COA/periode) membatalkan seluruh transaksi.
            // Issue #436 butir 5: approve lewat PUT juga menolak stok negatif.
            afterCommit: $isNewlyApproved
                ? function (StockAdjustment $stockAdjustment) use ($postJournal, $postMovements, $stockPosting): void {
                    $stockPosting->assertNoNegativeStock($this->stockAdjustmentOutflows($stockAdjustment->refresh()));
                    $postJournal->execute($stockAdjustment->refresh());
                    $postMovements->execute($stockAdjustment->refresh());
                    $stockPosting->assertNoNegativeStockFrom(
                        $this->stockAdjustmentOutflows($stockAdjustment->refresh()),
                        $stockAdjustment->adjustment_date->copy()->startOfDay(),
                    );
                }
            : null,
        );

        $stockAdjustment->load(['warehouse', 'inventoryStocktake', 'journalEntry', 'items.product', 'items.unit']);

        return (new StockAdjustmentResource($stockAdjustment))->response();
    }

    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        return $this->destroyDraftDocument($stockAdjustment);
    }

    public function export(ExportStockAdjustmentRequest $request, ExportStockAdjustmentsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return ['warehouse', 'inventoryStocktake', 'journalEntry', 'items.product', 'items.unit'];
    }

    /**
     * Baris penyesuaian negatif sebagai pengeluaran (Issue #436 butir 3).
     *
     * @return array<int, array{0:int,1:int,2:float}>
     */
    private function stockAdjustmentOutflows(StockAdjustment $stockAdjustment): array
    {
        return $stockAdjustment->load('items')->items
            ->filter(fn ($item): bool => (float) $item->quantity_adjusted < 0)
            ->map(fn ($item): array => [
                (int) $item->product_id,
                (int) $stockAdjustment->warehouse_id,
                abs((float) $item->quantity_adjusted),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $items
     */
    private function updateApprovedAdjustment(
        StockAdjustment $stockAdjustment,
        array $validated,
        array $items,
        SyncStockAdjustmentItemsAction $syncItems,
        StockPostingService $stockPosting,
    ): void {
        DB::transaction(function () use ($stockAdjustment, $validated, $items, $syncItems, $stockPosting): void {
            $stockAdjustment->update(UpdateStockAdjustmentData::fromArray($validated)->toArray());

            // Item diganti (bukan di-upsert) agar source_line_id movement asli tidak tertimpa
            // repost; pembalik tetap utuh di riwayat (Issue #372).
            // editDocument menghitung ulang saldo lalu menolak saldo negatif yang memburuk (422).
            $stockPosting->editDocument(
                StockAdjustment::class,
                $stockAdjustment->id,
                'adjustment',
                function () use ($stockAdjustment, $items, $syncItems, $stockPosting): void {
                    $stockAdjustment->items()->delete();
                    $syncItems->execute($stockAdjustment, $items);
                    $stockPosting->postStockAdjustment($stockAdjustment->refresh());
                },
            );

        });
    }
}
