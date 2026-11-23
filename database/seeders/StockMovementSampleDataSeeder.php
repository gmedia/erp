<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use App\Services\StockPostingService;
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
    public function run(): void
    {
        $stockPosting = app(StockPostingService::class);

        // Rebuild the ledger so re-seeding never leaves stale or duplicate rows.
        StockMovement::query()->delete();

        GoodsReceipt::query()
            ->where('status', 'confirmed')
            ->with('items')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->each(fn (GoodsReceipt $receipt) => $stockPosting->postGoodsReceipt($receipt));

        SupplierReturn::query()
            ->where('status', 'confirmed')
            ->with('items')
            ->orderBy('return_date')
            ->orderBy('id')
            ->each(fn (SupplierReturn $return) => $stockPosting->postSupplierReturn($return));

        StockAdjustment::query()
            ->where('status', 'approved')
            ->with('items')
            ->orderBy('adjustment_date')
            ->orderBy('id')
            ->each(fn (StockAdjustment $adjustment) => $stockPosting->postStockAdjustment($adjustment));

        StockTransfer::query()
            ->whereIn('status', ['in_transit', 'received'])
            ->with('items')
            ->orderBy('transfer_date')
            ->orderBy('id')
            ->each(function (StockTransfer $transfer) use ($stockPosting): void {
                $stockPosting->postStockTransferDispatch($transfer);
                $stockPosting->postStockTransferReceive($transfer);
            });

        $stockPosting->rebuildBalances();

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
     * Jurnal persediaan demo (GR, retur, adjustment) lewat action aplikasi,
     * setelah movement dibuat. Jurnal lama dilepas dulu karena action
     * mengembalikan jurnal yang sudah ada bila `journal_entry_id` terisi.
     */
    private function postInventoryJournals(): void
    {
        $staleEntryIds = JournalEntry::query()
            ->whereIn('source_type', [
                GoodsReceipt::class,
                SupplierReturn::class,
                StockAdjustment::class,
            ])
            ->pluck('id');

        if ($staleEntryIds->isNotEmpty()) {
            GoodsReceipt::query()->whereIn('journal_entry_id', $staleEntryIds)->update(['journal_entry_id' => null]);
            SupplierReturn::query()->whereIn('journal_entry_id', $staleEntryIds)->update(['journal_entry_id' => null]);
            StockAdjustment::query()->whereIn('journal_entry_id', $staleEntryIds)->update(['journal_entry_id' => null]);
            JournalEntryLine::query()->whereIn('journal_entry_id', $staleEntryIds)->delete();
            JournalEntry::query()->whereIn('id', $staleEntryIds)->delete();
        }

        $postGoodsReceipt = app(PostGoodsReceiptJournalAction::class);
        $postSupplierReturn = app(PostSupplierReturnJournalAction::class);
        $postStockAdjustment = app(PostStockAdjustmentJournalAction::class);

        GoodsReceipt::query()
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->get()
            ->each(fn (GoodsReceipt $receipt) => $postGoodsReceipt->execute($receipt->refresh()));

        SupplierReturn::query()
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->get()
            ->each(fn (SupplierReturn $return) => $postSupplierReturn->execute($return->refresh()));

        StockAdjustment::query()
            ->where('status', 'approved')
            ->orderBy('id')
            ->get()
            ->each(fn (StockAdjustment $adjustment) => $postStockAdjustment->execute($adjustment->refresh()));
    }
}
