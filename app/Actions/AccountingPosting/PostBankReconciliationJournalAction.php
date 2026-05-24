<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class PostBankReconciliationJournalAction
{
    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

    public function execute(BankReconciliation $bankReconciliation): ?JournalEntry
    {
        // Idempotency: if already posted, return existing
        if ($bankReconciliation->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $bankReconciliation->journalEntry()->first();

            return $existing;
        }

        // Status guard
        if ($bankReconciliation->status !== 'completed') {
            return null;
        }

        // Get unmatched items with account_id assigned
        $unmatchedWithAccount = $bankReconciliation->items()
            ->where('is_reconciled', false)
            ->whereNotNull('account_id')
            ->get();

        // If no items to post, skip
        if ($unmatchedWithAccount->isEmpty()) {
            return null;
        }

        $bankReconciliation->loadMissing('account');

        return DB::transaction(function () use ($bankReconciliation, $unmatchedWithAccount): JournalEntry {
            $bankAccountId = $bankReconciliation->account_id;
            $lines = [];

            /** @var BankReconciliationItem $item */
            foreach ($unmatchedWithAccount as $item) {
                $debit = (float) $item->debit;
                $credit = (float) $item->credit;
                $memo = $item->description;

                if ($debit > 0) {
                    // Bank item is a withdrawal/charge: debit expense account, credit bank
                    $lines[] = ['account_id' => $item->account_id, 'debit' => $debit, 'credit' => 0, 'memo' => $memo];
                    $lines[] = ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => $debit, 'memo' => $memo];
                }

                if ($credit > 0) {
                    // Bank item is a deposit/income: debit bank, credit income account
                    $lines[] = ['account_id' => $bankAccountId, 'debit' => $credit, 'credit' => 0, 'memo' => $memo];
                    $lines[] = ['account_id' => $item->account_id, 'debit' => 0, 'credit' => $credit, 'memo' => $memo];
                }
            }

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $bankReconciliation->reconciliation_date->format('Y-m-d'),
                'reference' => "RECON-{$bankReconciliation->id}",
                'description' => "Bank Reconciliation - {$bankReconciliation->account->code}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => BankReconciliation::class,
                'source_id' => $bankReconciliation->id,
                'lines' => $lines,
            ]);

            $bankReconciliation->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
