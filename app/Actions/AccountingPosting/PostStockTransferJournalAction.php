<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

/**
 * Jurnal transfer antar gudang hanya untuk selisih saat diterima.
 *
 * Barang dalam perjalanan (in_transit) tetap persediaan: tidak ada jurnal.
 * Kekurangan saat diterima (quantity - quantity_received) x unit_cost
 * dijurnal sebagai beban selisih persediaan (Dr 51000 / Cr 11300).
 */
class PostStockTransferJournalAction
{
    public const INVENTORY_ACCOUNT_CODE = '11300';

    public const SHORTAGE_EXPENSE_CODE = PostStockAdjustmentJournalAction::STOCK_ADJUSTMENT_EXPENSE_CODE;

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(StockTransfer $stockTransfer): ?JournalEntry
    {
        if ($stockTransfer->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $stockTransfer->journalEntry()->first();

            return $existing;
        }

        if ($stockTransfer->status !== 'received') {
            return null;
        }

        return DB::transaction(function () use ($stockTransfer): ?JournalEntry {
            $stockTransfer->loadMissing('items', 'toWarehouse');

            $shortage = 0.0;

            /** @var StockTransferItem $item */
            foreach ($stockTransfer->items as $item) {
                $missing = (float) $item->quantity - (float) $item->quantity_received;

                if ($missing > 0) {
                    $shortage += $missing * (float) $item->unit_cost;
                }
            }

            $shortage = round($shortage, 2);

            if ($shortage <= 0) {
                return null;
            }

            $entryDate = ($stockTransfer->received_at ?? $stockTransfer->transfer_date)->format('Y-m-d');
            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE, $entryDate);
            $shortageAccount = $this->resolveControlAccount->execute(self::SHORTAGE_EXPENSE_CODE, $entryDate);
            $memo = "Transfer {$stockTransfer->transfer_number}";

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $stockTransfer->transfer_number,
                'description' => "Selisih transfer {$stockTransfer->transfer_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => StockTransfer::class,
                'source_id' => $stockTransfer->id,
                'branch_id' => $stockTransfer->toWarehouse->branch_id,
                'lines' => [
                    [
                        'account_id' => $shortageAccount->id,
                        'debit' => $shortage,
                        'credit' => 0,
                        'memo' => $memo,
                    ],
                    [
                        'account_id' => $inventoryAccount->id,
                        'debit' => 0,
                        'credit' => $shortage,
                        'memo' => $memo,
                    ],
                ],
            ]);

            $stockTransfer->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
