<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostGoodsReceiptJournalAction
{
    public const INVENTORY_ACCOUNT_CODE = '11300';

    public const AP_CONTROL_ACCOUNT_CODE = '21100';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(GoodsReceipt $goodsReceipt): ?JournalEntry
    {
        if ($goodsReceipt->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $goodsReceipt->journalEntry()->first();

            return $existing;
        }

        if ($goodsReceipt->status !== 'confirmed') {
            return null;
        }

        return DB::transaction(function () use ($goodsReceipt): JournalEntry {
            $goodsReceipt->loadMissing('items', 'warehouse');

            if ($goodsReceipt->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a goods receipt with no items.',
                ]);
            }

            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE);
            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE);

            $total = 0.0;

            foreach ($goodsReceipt->items as $item) {
                /** @var GoodsReceiptItem $item */
                $total += (float) $item->quantity_accepted * (float) $item->unit_price;
            }

            $total = round($total, 2);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a goods receipt with zero or negative total value.',
                ]);
            }

            $memo = "GR {$goodsReceipt->gr_number}";

            $lines = [
                [
                    'account_id' => $inventoryAccount->id,
                    'debit' => $total,
                    'credit' => 0,
                    'memo' => $memo,
                ],
                [
                    'account_id' => $apAccount->id,
                    'debit' => 0,
                    'credit' => $total,
                    'memo' => $memo,
                ],
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $goodsReceipt->receipt_date->format('Y-m-d'),
                'reference' => $goodsReceipt->gr_number,
                'description' => "Goods Receipt {$goodsReceipt->gr_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => GoodsReceipt::class,
                'source_id' => $goodsReceipt->id,
                'branch_id' => $goodsReceipt->warehouse->branch_id,
                'lines' => $lines,
            ]);

            $goodsReceipt->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
