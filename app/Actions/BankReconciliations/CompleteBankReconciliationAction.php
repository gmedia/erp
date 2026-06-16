<?php

namespace App\Actions\BankReconciliations;

use App\Actions\AccountingPosting\PostBankReconciliationJournalAction;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompleteBankReconciliationAction
{
    public function __construct(
        private PostBankReconciliationJournalAction $postJournal,
    ) {}

    public function execute(BankReconciliation $bankReconciliation): BankReconciliation
    {
        if (bccomp((string) $bankReconciliation->difference, '0', 2) !== 0) {
            throw ValidationException::withMessages(['difference' => 'Bank reconciliation difference must be zero.']);
        }

        DB::transaction(function () use ($bankReconciliation): void {
            $bankReconciliation->update([
                'status' => 'completed',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            try {
                $this->postJournal->execute($bankReconciliation->refresh());
            } catch (Throwable $e) {
                // Best-effort posting: journal failures are intentionally swallowed
                // so the reconciliation itself stays completed. Wrapping in a
                // transaction still closes the race where the process dies between
                // the status update commit and the journal posting call — in that
                // window the update would roll back atomically.
                Log::warning('Bank reconciliation journal posting failed', [
                    'bank_reconciliation_id' => $bankReconciliation->id,
                    'error' => $e->getMessage(),
                ]);
            }
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
