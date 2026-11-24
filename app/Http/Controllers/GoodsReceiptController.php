<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\GoodsReceipts\EnsurePurchaseOrderAllowsConfirmedReceiptAction;
use App\Actions\GoodsReceipts\ExportGoodsReceiptsAction;
use App\Actions\GoodsReceipts\IndexGoodsReceiptsAction;
use App\Actions\GoodsReceipts\ListGoodsReceiptItemOptionsAction;
use App\Actions\GoodsReceipts\PostGoodsReceiptMovementsAction;
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
use App\Services\PurchaseOrderReceiptService;
use App\Services\StockPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexGoodsReceiptRequest $request, IndexGoodsReceiptsAction $action): JsonResponse
    {
        $goodsReceipts = $action->execute($request);

        return (new GoodsReceiptCollection($goodsReceipts))->response();
    }

    public function store(
        StoreGoodsReceiptRequest $request,
        SyncGoodsReceiptItemsAction $syncItems,
        PostGoodsReceiptJournalAction $postJournal,
        PostGoodsReceiptMovementsAction $postMovements,
        EnsurePurchaseOrderAllowsConfirmedReceiptAction $guardPurchaseOrder,
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

        // Issue #367: GR yang dibuat langsung confirmed harus membentuk stok dan jurnal
        // dalam transaksi yang sama. Kegagalan posting ikut membatalkan dokumen.
        $goodsReceipt = DB::transaction(function () use (
            $validated,
            $items,
            $isConfirmedOnCreate,
            $guardPurchaseOrder,
            $syncItems,
            $postJournal,
            $postMovements,
        ): GoodsReceipt {
            if ($isConfirmedOnCreate) {
                $guardPurchaseOrder->execute((int) $validated['purchase_order_id']);
            }

            return $this->storeWithSyncedItems(
                attributes: $validated,
                items: $items,
                creator: static fn (array $attributes): GoodsReceipt => GoodsReceipt::create($attributes),
                assignDocumentNumber: function (GoodsReceipt $goodsReceipt): void {
                    $this->assignSequentialDocumentNumber($goodsReceipt, 'gr_number', 'GR');
                },
                syncItems: function (GoodsReceipt $goodsReceipt, array $items) use ($syncItems): void {
                    $syncItems->execute($goodsReceipt, $items);
                },
                afterCommit: $isConfirmedOnCreate
                    ? function (GoodsReceipt $goodsReceipt) use ($postJournal, $postMovements): void {
                        $postJournal->execute($goodsReceipt->refresh());
                        $postMovements->execute($goodsReceipt->refresh());
                    }
                : null,
            );
        });

        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response()->setStatusCode(201);
    }

    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response();
    }

    public function update(
        UpdateGoodsReceiptRequest $request,
        GoodsReceipt $goodsReceipt,
        SyncGoodsReceiptItemsAction $syncItems,
        PostGoodsReceiptJournalAction $postJournal,
        PostGoodsReceiptMovementsAction $postMovements,
        StockPostingService $stockPosting,
        PurchaseOrderReceiptService $purchaseOrderReceipts,
        EnsurePurchaseOrderAllowsConfirmedReceiptAction $guardPurchaseOrder,
    ): JsonResponse {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $isNewlyConfirmed = ($validated['status'] ?? null) === 'confirmed' && $goodsReceipt->confirmed_at === null;
        $isCancellingConfirmed = ($validated['status'] ?? null) === 'cancelled'
            && $goodsReceipt->status === 'confirmed';

        if ($isNewlyConfirmed) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        if ($isCancellingConfirmed) {
            // Issue #356 baris 3: GR confirmed yang dibatalkan harus membalik stok
            // dan received qty PO, dalam transaksi yang sama dengan perubahan status.
            DB::transaction(function () use ($goodsReceipt, $validated, $stockPosting, $purchaseOrderReceipts): void {
                $goodsReceipt->update(UpdateGoodsReceiptData::fromArray($validated)->toArray());
                $stockPosting->reverseDocument(GoodsReceipt::class, $goodsReceipt->id, 'goods_receipt');
                $purchaseOrderReceipts->syncFromGoodsReceipt($goodsReceipt->refresh());
            });

            return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response();
        }

        DB::transaction(function () use (
            $goodsReceipt,
            $validated,
            $items,
            $isNewlyConfirmed,
            $guardPurchaseOrder,
            $syncItems,
            $postJournal,
            $postMovements,
            $stockPosting,
            $purchaseOrderReceipts,
        ): void {
            // GR yang sudah confirmed tetap sah; aturan PO hanya berlaku saat GR baru dikonfirmasi.
            if ($isNewlyConfirmed) {
                $purchaseOrderId = $validated['purchase_order_id'] ?? $goodsReceipt->purchase_order_id;
                $guardPurchaseOrder->execute((int) $purchaseOrderId);
            }

            $this->updateWithSyncedItems(
                model: $goodsReceipt,
                attributes: $validated,
                items: $items,
                payloadResolver: static function (array $attributes): array {
                    return UpdateGoodsReceiptData::fromArray($attributes)->toArray();
                },
                syncItems: function (GoodsReceipt $goodsReceipt, array $items) use ($syncItems): void {
                    $syncItems->execute($goodsReceipt, $items);
                },
                afterCommit: $isNewlyConfirmed
                    // Jurnal gagal (COA/periode) melempar ValidationException: transaksi dibatalkan,
                    // dokumen dan stok tidak berubah (Issue #416).
                    ? function (GoodsReceipt $goodsReceipt) use ($postJournal): void {
                        $postJournal->execute($goodsReceipt->refresh());
                    }
                : null,
            );

            // Issue #416: dokumen, jurnal, dan stok satu transaksi; bila salah satu ditolak
            // (422), tidak ada yang berubah.
            if ($isNewlyConfirmed) {
                $postMovements->execute($goodsReceipt->refresh());
            } elseif ($goodsReceipt->status === 'confirmed' && is_array($items)) {
                // Issue #356: item GR confirmed diedit setelah posting. Movement lama dibalik
                // (histori tetap), lalu item baru diposting ulang.
                $stockPosting->editDocument(
                    GoodsReceipt::class,
                    $goodsReceipt->id,
                    'goods_receipt',
                    fn () => $postMovements->execute($goodsReceipt->refresh()),
                );
                $purchaseOrderReceipts->syncFromGoodsReceipt($goodsReceipt);
            }
        });

        return (new GoodsReceiptResource($this->loadResourceRelations($goodsReceipt)))->response();
    }

    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        return $this->destroyModel($goodsReceipt);
    }

    public function itemOptions(
        GoodsReceipt $goodsReceipt,
        ListGoodsReceiptItemOptionsAction $action,
        Request $request,
    ): JsonResponse {
        return response()->json(['data' => $action->execute(
            $goodsReceipt,
            $request->string('search')->toString() ?: null,
            $request->integer('current_line_id') ?: null,
            $request->integer('supplier_return_id') ?: null,
        )]);
    }

    public function export(ExportGoodsReceiptRequest $request, ExportGoodsReceiptsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return [
            'purchaseOrder.supplier',
            'warehouse',
            'receiver',
            'confirmer',
            'creator',
            'items.product',
            'items.unit',
        ];
    }
}
