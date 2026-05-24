<?php

namespace App\Actions\BankReconciliations;

use App\Models\BankReconciliationItem;

class UnmatchBankReconciliationItemAction
{
    public function execute(BankReconciliationItem $item): BankReconciliationItem
    {
        $item->update([
            'journal_entry_line_id' => null,
            'is_reconciled' => false,
        ]);

        return $item->refresh();
    }
}
