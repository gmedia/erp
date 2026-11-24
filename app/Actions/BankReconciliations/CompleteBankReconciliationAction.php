<?php

namespace App\Actions\BankReconciliations;

use App\Actions\AccountingPosting\PostBankReconciliationJournalAction;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteBankReconciliationAction
{
    public function __construct(
        private PostBankReconciliationJournalAction $postJournal,
    ) {}

    public function execute(BankReconciliation $bankReconciliation): BankReconciliation
    {
        if (in_array($bankReconciliation->status, ['completed', 'cancelled'], true)) {
            $message = 'Bank reconciliation is already ' . $bankReconciliation->status . '.';

            throw ValidationException::withMessages(['status' => $message]);
        }

        if (bccomp((string) $bankReconciliation->difference, '0', 2) !== 0) {
            throw ValidationException::withMessages(['difference' => 'Bank reconciliation difference must be zero.']);
        }

        $hasUnmatchedWithoutAccount = $bankReconciliation->items()
            ->where('is_reconciled', false)
            ->whereNull('account_id')
            ->exists();

        if ($hasUnmatchedWithoutAccount) {
            $message = 'Every unmatched bank item must have an account before completing.';

            throw ValidationException::withMessages(['items' => $message]);
        }

        // Issue #416: status dan jurnal satu transaksi. Jurnal gagal (ValidationException,
        // mis. COA/periode) membatalkan seluruh transaksi: status tetap, respons 422.
        DB::transaction(function () use ($bankReconciliation): void {
            $bankReconciliation->update([
                'status' => 'completed',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            $this->postJournal->execute($bankReconciliation->refresh());
        });

        return $bankReconciliation->refresh()->load([
            'account',
            'fiscalYear',
            'items.account',
            'items.journalEntryLine.journalEntry',
            'completedBy',
            'creator',
        ]);
    }
}
