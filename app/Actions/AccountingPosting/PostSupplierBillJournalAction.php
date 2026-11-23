<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierBillJournalAction
{
    public const AP_CONTROL_ACCOUNT_CODE = '21100';

    /**
     * Tagihan yang sudah dikonfirmasi lalu berubah menjadi overdue/partially_paid/paid
     * tetap harus punya jurnal AP; draft/cancelled/void tidak diposting.
     *
     * @var list<string>
     */
    public const POSTABLE_STATUSES = ['confirmed', 'overdue', 'partially_paid', 'paid'];

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(SupplierBill $supplierBill): ?JournalEntry
    {
        if ($supplierBill->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $supplierBill->journalEntry()->first();

            return $existing;
        }

        if (! in_array($supplierBill->status, self::POSTABLE_STATUSES, true)) {
            return null;
        }

        return DB::transaction(function () use ($supplierBill): JournalEntry {
            $supplierBill->loadMissing('items');

            if ($supplierBill->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a supplier bill with no items.',
                ]);
            }

            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE);

            $debitTotal = 0.0;
            $debitLines = [];
            $expenseGrouped = [];

            // Bill atas GR: akun debit = akun yang benar-benar dikredit jurnal GR terkait
            // (GR/IR untuk GR baru, 21100 untuk GR lama), supaya utang tidak tercatat ganda.
            $grCreditAccountId = $this->grCreditAccountIdFor($supplierBill);

            foreach ($supplierBill->items as $item) {
                $accountId = $item->goods_receipt_item_id !== null && $grCreditAccountId !== null
                    ? $grCreditAccountId
                    : (int) $item->account_id;
                $expenseGrouped[$accountId] = ($expenseGrouped[$accountId] ?? 0.0) + (float) $item->line_total;
            }

            foreach ($expenseGrouped as $accountId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $debitLines[] = [
                    'account_id' => $accountId,
                    'debit' => round($amount, 2),
                    'credit' => 0,
                    'memo' => "Bill {$supplierBill->bill_number}",
                ];
                $debitTotal += $amount;
            }

            $debitTotal = round($debitTotal, 2);

            $creditLine = [
                'account_id' => $apAccount->id,
                'debit' => 0,
                'credit' => $debitTotal,
                'memo' => "Bill {$supplierBill->bill_number}",
            ];

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $supplierBill->bill_date->format('Y-m-d'),
                'reference' => $supplierBill->bill_number,
                'description' => "Supplier Bill {$supplierBill->bill_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => SupplierBill::class,
                'source_id' => $supplierBill->id,
                'branch_id' => $supplierBill->branch_id,
                'lines' => array_merge($debitLines, [$creditLine]),
            ]);

            $supplierBill->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }

    /**
     * Akun kredit pada jurnal GR terkait yang bukan AP. Null bila bill tanpa GR
     * atau GR belum punya jurnal; bill tanpa GR memakai akun item seperti biasa.
     */
    private function grCreditAccountIdFor(SupplierBill $supplierBill): ?int
    {
        if ($supplierBill->goods_receipt_id === null) {
            return null;
        }

        $goodsReceipt = GoodsReceipt::query()->find($supplierBill->goods_receipt_id);

        if ($goodsReceipt === null || $goodsReceipt->journal_entry_id === null) {
            return null;
        }

        // Jurnal GR hanya punya satu sisi kredit (GR/IR atau AP lama); ambil akun itu apa adanya.
        $creditLine = JournalEntryLine::query()
            ->where('journal_entry_id', $goodsReceipt->journal_entry_id)
            ->where('credit', '>', 0)
            ->first();

        return $creditLine?->account_id;
    }
}
