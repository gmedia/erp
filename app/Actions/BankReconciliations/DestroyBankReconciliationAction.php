<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;

class DestroyBankReconciliationAction
{
    public function execute(BankReconciliation $bankReconciliation): void
    {
        DB::transaction(function () use ($bankReconciliation): void {
            $bankReconciliation->items()->delete();
            $bankReconciliation->delete();
        });
    }
}
