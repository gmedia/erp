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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

        // Issue #367: penyesuaian yang dibuat langsung approved harus memposting jurnal
        // dalam transaksi yang sama. Movement stok diposting setelah commit (W5-19).
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
                ? function (StockAdjustment $adjustment) use ($postJournal): void {
                    try {
                        $postJournal->execute($adjustment->refresh());
                    } catch (ValidationException $e) {
                        // Sama dengan jalur update: validasi (mis. COA belum lengkap) tidak
                        // membatalkan penyesuaian; jurnal bisa diposting ulang nanti.
                        Log::warning('Stock adjustment journal posting skipped', [
                            'stock_adjustment_id' => $adjustment->id,
                            'reason' => $e->getMessage(),
                        ]);
                    }
                }
            : null,
        );

        if ($isApprovedOnCreate) {
            $postMovements->execute($adjustment->refresh());
        }

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

        if ($isNewlyApproved) {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now()->toIso8601String();
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
            afterCommit: $isNewlyApproved
                ? function (StockAdjustment $stockAdjustment) use ($postJournal): void {
                    try {
                        $postJournal->execute($stockAdjustment->refresh());
                    } catch (ValidationException $e) {
                        // Validation failures (e.g. missing COA mapping) are intentionally
                        // swallowed so the stock adjustment itself stays approved; user can
                        // post the journal later. Non-validation errors propagate and
                        // roll back the entire transaction (status flip included).
                        Log::warning('Stock adjustment journal posting skipped', [
                            'stock_adjustment_id' => $stockAdjustment->id,
                            'reason' => $e->getMessage(),
                        ]);
                    }
                }
            : null,
        );

        if ($isNewlyApproved) {
            // Setelah transaksi commit: movement tidak boleh ikut rollback bila
            // posting jurnal gagal (W5-19).
            $postMovements->execute($stockAdjustment->refresh());
        }

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
}
