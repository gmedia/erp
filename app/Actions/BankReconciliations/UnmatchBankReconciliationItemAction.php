<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliationItem;
use Illuminate\Support\Facades\DB;

class UnmatchBankReconciliationItemAction
{
    public function execute(BankReconciliationItem $item): BankReconciliationItem
    {
        return DB::transaction(function () use ($item): BankReconciliationItem {
            $item->update([
                'journal_entry_line_id' => null,
                'is_reconciled' => false,
            ]);

            $item->bankReconciliation->recalculateBalances();

            return $item->refresh();
        });
    }
}
