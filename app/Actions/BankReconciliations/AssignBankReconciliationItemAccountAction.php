<?php

namespace App\Actions\BankReconciliations;

use App\Http\Requests\BankReconciliations\AssignBankReconciliationItemAccountRequest;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;

class AssignBankReconciliationItemAccountAction
{
    public function execute(
        AssignBankReconciliationItemAccountRequest $request,
        BankReconciliation $bankReconciliation,
        int $itemId,
    ): BankReconciliationItem {
        /** @var BankReconciliationItem $bankItem */
        $bankItem = $bankReconciliation->items()->findOrFail($itemId);
        $bankItem->update($request->validated());

        return $bankItem->refresh();
    }
}
