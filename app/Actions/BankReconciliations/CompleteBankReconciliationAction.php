<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliation;
use Illuminate\Validation\ValidationException;

class CompleteBankReconciliationAction
{
    public function execute(BankReconciliation $bankReconciliation): BankReconciliation
    {
        if (bccomp((string) $bankReconciliation->difference, '0', 2) !== 0) {
            throw ValidationException::withMessages(['difference' => 'Bank reconciliation difference must be zero.']);
        }

        $bankReconciliation->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return $bankReconciliation->refresh()->load(['account', 'fiscalYear', 'items', 'completedBy', 'creator']);
    }
}
