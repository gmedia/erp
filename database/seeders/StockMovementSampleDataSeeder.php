<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Actions\InventoryStocktakes\CreateStocktakeAdjustmentDraftAction;
use App\Models\GoodsReceipt;
use App\Models\InventoryStocktake;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use App\Services\StockPostingService;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;

/**
 * Post stock movements for every demo document that changes stock, using the
 * same StockPostingService as the application (W7-04). Only documents in a
 * stock-affecting state produce movements:
 *   - goods receipt `confirmed`
 *   - supplier return `confirmed`
 *   - stock adjustment `approved`
 *   - stock transfer `in_transit` (dispatch) and `received` (dispatch + receive)
 *
 * Movements are rebuilt from scratch each run (idempotent) and the
 * `balance_after` chain is recomputed in (moved_at, id) order.
 */
class StockMovementSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    /** Pola nomor dokumen demo yang dibuat seeder (lihat seeder masing-masing modul). */
    private const GR_DEMO = 'GR-____-0001%';

    private const SR_DEMO = 'SR-____-0001%';

    // Nomor demo SA/ST berurutan kecil (-000001..). Nomor lama 9100xx/9000xx
    // sudah di-rename oleh seeder masing-masing, jadi pola lama tidak dipakai lagi.
    private const SA_DEMO = 'SA-____-00000_';

    private const ST_DEMO = 'ST-____-00000_';

    protected function seedDemoData(): void
    {
        $stockPosting = app(StockPostingService::class);

        // Rebuild only the movements that belong to demo documents. Movements of
        // user-created documents are never touched (issue #357).
        $this->deleteDemoStockMovements();

        GoodsReceipt::query()
            ->where('status', 'confirmed')
            ->where('gr_number', 'like', self::GR_DEMO)
            ->with('items')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->each(fn (GoodsReceipt $receipt) => $stockPosting->postGoodsReceipt($receipt));

        SupplierReturn::query()
            ->where('status', 'confirmed')
            ->where('return_number', 'like', self::SR_DEMO)
            ->with('items')
            ->orderBy('return_date')
            ->orderBy('id')
            ->each(fn (SupplierReturn $return) => $stockPosting->postSupplierReturn($return));

        StockAdjustment::query()
            ->where('status', 'approved')
            ->where('adjustment_number', 'like', self::SA_DEMO)
            ->with('items')
            ->orderBy('adjustment_date')
            ->orderBy('id')
            ->each(fn (StockAdjustment $adjustment) => $stockPosting->postStockAdjustment($adjustment));

        StockTransfer::query()
            ->whereIn('status', ['in_transit', 'received'])
            ->where('transfer_number', 'like', self::ST_DEMO)
            ->with('items')
            ->orderBy('transfer_date')
            ->orderBy('id')
            ->each(function (StockTransfer $transfer) use ($stockPosting): void {
                $stockPosting->postStockTransferDispatch($transfer);
                $stockPosting->postStockTransferReceive($transfer);
            });

        $stockPosting->rebuildBalances();

        // Qty sistem stok opname dan draft SA stocktake_result dibentuk setelah saldo
        // kartu stok tersedia (#459 butir 1-2). Urutan ini harus sebelum journal.
        $this->syncStocktakeFromStockCard();

        $this->postInventoryJournals();

        // Keep the approved adjustment's recorded `quantity_after` in sync with
        // the recomputed ledger balance so the detail view matches the card.
        $approvedAdjustments = StockAdjustment::query()
            ->where('status', 'approved')
            ->with('items')
            ->get();

        foreach ($approvedAdjustments as $adjustment) {
            foreach ($adjustment->items as $item) {
                $balance = StockMovement::query()
                    ->where('reference_type', StockAdjustment::class)
                    ->where('reference_id', $adjustment->id)
                    ->where('product_id', $item->product_id)
                    ->value('balance_after');

                if ($balance !== null) {
                    StockAdjustmentItem::query()
                        ->whereKey($item->getKey())
                        ->update(['quantity_after' => (float) $balance]);
                }
            }
        }
    }

    /**
     * Qty sistem baris stok opname diambil dari saldo kartu stok pada tanggal opname
     * (#459 butir 1). Opname completed yang berselisih lalu memakai alur aplikasi:
     * satu draft SA stocktake_result lewat CreateStocktakeAdjustmentDraftAction (#459 butir 2).
     */
    private function syncStocktakeFromStockCard(): void
    {
        InventoryStocktake::query()
            ->where('stocktake_number', 'like', 'STO-%')
            ->with('items')
            ->orderBy('id')
            ->get()
            ->each(function (InventoryStocktake $stocktake): void {
                foreach ($stocktake->items as $index => $line) {
                    $systemQuantity = $this->stockCardBalance(
                        (int) $line->product_id,
                        (int) $stocktake->warehouse_id,
                        (string) $stocktake->stocktake_date,
                    );

                    $line->system_quantity = $systemQuantity;

                    if ($line->counted_quantity !== null) {
                        $line->counted_quantity = $this->demoCountedQuantity(
                            $stocktake->status,
                            $index,
                            $systemQuantity,
                        );
                        $line->variance = round((float) $line->counted_quantity - $systemQuantity, 2);
                        $line->result = $this->resultFor((float) $line->variance);
                    }

                    $line->save();
                }

                // Draft lama dari seed sebelumnya dilepas dulu. CreateStocktakeAdjustmentDraftAction
                // melewati stocktake yang sudah punya SA, sehingga quantity_before tidak ikut saldo baru.
                $this->deleteStocktakeAdjustmentDrafts($stocktake);

                if ($stocktake->status === 'completed') {
                    app(CreateStocktakeAdjustmentDraftAction::class)->execute($stocktake->refresh());
                }
            });
    }

    /**
     * Lepas draft SA stocktake_result milik opname demo beserta barisnya. Hanya SA
     * tanpa jurnal yang dilepas; SA yang sudah disetujui tidak disentuh di sini.
     */
    private function deleteStocktakeAdjustmentDrafts(InventoryStocktake $stocktake): void
    {
        $adjustmentIds = StockAdjustment::query()
            ->where('inventory_stocktake_id', $stocktake->id)
            ->where('adjustment_type', 'stocktake_result')
            ->where('status', 'draft')
            ->pluck('id');

        if ($adjustmentIds->isEmpty()) {
            return;
        }

        StockAdjustmentItem::query()->whereIn('stock_adjustment_id', $adjustmentIds)->delete();
        StockAdjustment::query()->whereIn('id', $adjustmentIds)->delete();
    }

    /**
     * Hitung fisik demo relatif terhadap saldo kartu stok: baris pertama sesuai,
     * kedua surplus +3, sisanya defisit −2 (pola selisih demo lama).
     */
    private function demoCountedQuantity(string $status, int $index, float $systemQuantity): float
    {
        if ($status !== 'completed') {
            return $systemQuantity;
        }

        return match ($index) {
            0 => $systemQuantity,
            1 => $systemQuantity + 3.0,
            default => max(0.0, $systemQuantity - 2.0),
        };
    }

    private function resultFor(float $variance): string
    {
        return match (true) {
            $variance === 0.0 => 'match',
            $variance > 0.0 => 'surplus',
            default => 'deficit',
        };
    }

    private function stockCardBalance(int $productId, int $warehouseId, string $date): float
    {
        $balance = StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereDate('moved_at', '<=', $date)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after');

        return (float) ($balance ?? 0.0);
    }

    /**
     * Hapus hanya movement milik dokumen demo (GR, retur, adjustment, transfer)
     * yang nomor dokumennya dibuat oleh seeder demo. Movement buatan pengguna
     * dibiarkan utuh; saat dokumen demo dibangun ulang, movement-nya ikut dibangun ulang.
     */
    private function deleteDemoStockMovements(): void
    {
        $demoIds = [
            GoodsReceipt::class => $this->demoIds(GoodsReceipt::class, 'gr_number', self::GR_DEMO),
            SupplierReturn::class => $this->demoIds(SupplierReturn::class, 'return_number', self::SR_DEMO),
            StockAdjustment::class => $this->demoIds(StockAdjustment::class, 'adjustment_number', self::SA_DEMO),
            StockTransfer::class => $this->demoIds(StockTransfer::class, 'transfer_number', self::ST_DEMO),
        ];

        StockMovement::query()
            ->where(function ($query) use ($demoIds): void {
                foreach ($demoIds as $referenceType => $ids) {
                    if ($ids === []) {
                        continue;
                    }

                    $query->orWhere(fn ($q) => $q
                        ->where('reference_type', $referenceType)
                        ->whereIn('reference_id', $ids));
                }
            })
            ->delete();
    }

    /**
     * @param  class-string  $modelClass
     * @return list<int>
     */
    private function demoIds(string $modelClass, string $numberColumn, string $demoPattern): array
    {
        return $modelClass::query()
            ->where($numberColumn, 'like', $demoPattern)
            ->pluck('id')
            ->all();
    }

    /**
     * Jurnal persediaan demo (GR, retur, adjustment) lewat action aplikasi,
     * setelah movement dibuat. Jurnal lama dilepas dulu karena action
     * mengembalikan jurnal yang sudah ada bila `journal_entry_id` terisi.
     * Hanya jurnal milik dokumen demo yang dilepas; jurnal pengguna tetap.
     */
    private function postInventoryJournals(): void
    {
        $demoEntryIds = JournalEntry::query()
            ->where(fn ($query) => $query
                ->where(fn ($q) => $q
                    ->where('source_type', GoodsReceipt::class)
                    ->whereIn('source_id', $this->demoIds(GoodsReceipt::class, 'gr_number', self::GR_DEMO)))
                ->orWhere(fn ($q) => $q
                    ->where('source_type', SupplierReturn::class)
                    ->whereIn('source_id', $this->demoIds(SupplierReturn::class, 'return_number', self::SR_DEMO)))
                ->orWhere(fn ($q) => $q
                    ->where('source_type', StockAdjustment::class)
                    ->whereIn('source_id', $this->demoIds(StockAdjustment::class, 'adjustment_number', self::SA_DEMO))))
            ->pluck('id');

        if ($demoEntryIds->isNotEmpty()) {
            GoodsReceipt::query()->whereIn('journal_entry_id', $demoEntryIds)->update(['journal_entry_id' => null]);
            SupplierReturn::query()->whereIn('journal_entry_id', $demoEntryIds)->update(['journal_entry_id' => null]);
            StockAdjustment::query()->whereIn('journal_entry_id', $demoEntryIds)->update(['journal_entry_id' => null]);
            JournalEntryLine::query()->whereIn('journal_entry_id', $demoEntryIds)->delete();
            JournalEntry::query()->whereIn('id', $demoEntryIds)->delete();
        }

        $postGoodsReceipt = app(PostGoodsReceiptJournalAction::class);
        $postSupplierReturn = app(PostSupplierReturnJournalAction::class);
        $postStockAdjustment = app(PostStockAdjustmentJournalAction::class);

        GoodsReceipt::query()
            ->where('status', 'confirmed')
            ->where('gr_number', 'like', self::GR_DEMO)
            ->orderBy('id')
            ->get()
            ->each(fn (GoodsReceipt $receipt) => $postGoodsReceipt->execute($receipt->refresh()));

        SupplierReturn::query()
            ->where('status', 'confirmed')
            ->where('return_number', 'like', self::SR_DEMO)
            ->orderBy('id')
            ->get()
            ->each(fn (SupplierReturn $return) => $postSupplierReturn->execute($return->refresh()));

        StockAdjustment::query()
            ->where('status', 'approved')
            ->where('adjustment_number', 'like', self::SA_DEMO)
            ->orderBy('id')
            ->get()
            ->each(fn (StockAdjustment $adjustment) => $postStockAdjustment->execute($adjustment->refresh()));
    }
}
