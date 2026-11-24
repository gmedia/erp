<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\StockAdjustments\ExportStockAdjustmentsAction;
use App\Actions\StockAdjustments\IndexStockAdjustmentsAction;
use App\Actions\StockAdjustments\PostStockAdjustmentMovementsAction;
use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\DTOs\StockAdjustments\UpdateStockAdjustmentData;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\StoreStockAdjustmentRequest;
use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentRequest;
use App\Http\Resources\StockAdjustments\StockAdjustmentCollection;
use App\Http\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\StockPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
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
                ? function (StockAdjustment $adjustment) use ($postJournal, $postMovements): void {
                    $postJournal->execute($adjustment->refresh());
                    $postMovements->execute($adjustment->refresh());
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
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

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
                // Issue #416: jurnal gagal (COA/periode) membatalkan seluruh transaksi.
                ? function (StockAdjustment $stockAdjustment) use ($postJournal, $postMovements): void {
                    $postJournal->execute($stockAdjustment->refresh());
                    $postMovements->execute($stockAdjustment->refresh());
                }
            : null,
        );

        $stockAdjustment->load(['warehouse', 'inventoryStocktake', 'journalEntry', 'items.product', 'items.unit']);

        return (new StockAdjustmentResource($stockAdjustment))->response();
    }

    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        // Issue #356 sibling: adjustment yang sudah approved dibatalkan lewat movement
        // pembalik (histori tidak dihapus), dalam transaksi yang sama.
        DB::transaction(function () use ($stockAdjustment): void {
            $wasApproved = $stockAdjustment->status === 'approved';

            $stockAdjustment->update(['status' => 'cancelled']);

            if ($wasApproved) {
                app(StockPostingService::class)->reverseDocument(
                    StockAdjustment::class,
                    $stockAdjustment->id,
                    'adjustment',
                );
            }
        });

        return response()->json(null, 204);
    }

    public function export(ExportStockAdjustmentRequest $request, ExportStockAdjustmentsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
