<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierBillJournalAction
{
    public const AP_CONTROL_ACCOUNT_CODE = '21100';

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

        if ($supplierBill->status !== 'confirmed') {
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

            foreach ($supplierBill->items as $item) {
                $accountId = (int) $item->account_id;
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
}
