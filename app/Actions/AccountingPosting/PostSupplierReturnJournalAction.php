<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierReturnJournalAction
{
    public const INVENTORY_ACCOUNT_CODE = '11300';

    /** Fallback debit retur bila GR asal belum berjurnal: akun GR/IR, bukan AP. */
    public const GRIR_CONTROL_ACCOUNT_CODE = '21150';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(SupplierReturn $supplierReturn): ?JournalEntry
    {
        if ($supplierReturn->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $supplierReturn->journalEntry()->first();

            return $existing;
        }

        if ($supplierReturn->status !== 'confirmed') {
            return null;
        }

        return DB::transaction(function () use ($supplierReturn): JournalEntry {
            $supplierReturn->loadMissing('items.goodsReceiptItem', 'warehouse');

            if ($supplierReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier return with no items.',
                ]);
            }

            $inventoryAccount = $this->resolveControlAccount->execute(self::INVENTORY_ACCOUNT_CODE);
            $fallbackAccount = $this->resolveControlAccount->execute(self::GRIR_CONTROL_ACCOUNT_CODE);

            $memo = "Return {$supplierReturn->return_number}";
            $total = 0.0;
            $debitGrouped = [];

            foreach ($supplierReturn->items as $item) {
                /** @var SupplierReturnItem $item */
                $amount = round((float) $item->quantity_returned * (float) $item->unit_price, 2);
                $total += $amount;

                // Keputusan #357 (4): debit akun yang dikredit jurnal GR asal (GR/IR untuk GR baru,
                // 21100 untuk GR lama). Utang 21100 hanya berubah lewat bill atau nota debit.
                $accountId = $this->grCreditAccountIdFor($item) ?? $fallbackAccount->id;
                $debitGrouped[$accountId] = ($debitGrouped[$accountId] ?? 0.0) + $amount;
            }

            $total = round($total, 2);

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier return with zero or negative total value.',
                ]);
            }

            $lines = [];

            foreach ($debitGrouped as $accountId => $amount) {
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => round($amount, 2),
                    'credit' => 0,
                    'memo' => $memo,
                ];
            }

            $lines[] = [
                'account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $total,
                'memo' => $memo,
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $supplierReturn->return_date->format('Y-m-d'),
                'reference' => $supplierReturn->return_number,
                'description' => "Supplier Return {$supplierReturn->return_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => SupplierReturn::class,
                'source_id' => $supplierReturn->id,
                'branch_id' => $supplierReturn->warehouse->branch_id,
                'lines' => $lines,
            ]);

            $supplierReturn->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }

    /**
     * Akun kredit jurnal GR asal baris retur (bukan AP). Null bila baris tanpa GR
     * atau GR belum berjurnal; pemanggil memakai akun GR/IR sebagai fallback.
     */
    private function grCreditAccountIdFor(SupplierReturnItem $item): ?int
    {
        $goodsReceipt = GoodsReceipt::query()->find($item->goodsReceiptItem->goods_receipt_id);

        if ($goodsReceipt === null || $goodsReceipt->journal_entry_id === null) {
            return null;
        }

        // Jurnal GR hanya punya satu sisi kredit (GR/IR atau AP lama); ambil akun itu apa adanya.
        return JournalEntryLine::query()
            ->where('journal_entry_id', $goodsReceipt->journal_entry_id)
            ->where('credit', '>', 0)
            ->value('account_id');
    }
}
