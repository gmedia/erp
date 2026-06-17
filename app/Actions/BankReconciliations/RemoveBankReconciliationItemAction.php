<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;

class RemoveBankReconciliationItemAction
{
    public function execute(BankReconciliation $bankReconciliation, int $itemId): void
    {
        DB::transaction(function () use ($bankReconciliation, $itemId): void {
            $bankReconciliation->items()->whereKey($itemId)->delete();

            $bankReconciliation->recalculateBalances();
        });
    }
}
