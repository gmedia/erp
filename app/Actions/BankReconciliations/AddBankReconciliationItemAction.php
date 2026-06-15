<?php

namespace App\Actions\BankReconciliations;

use App\Http\Requests\BankReconciliations\AddBankReconciliationItemRequest;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;

class AddBankReconciliationItemAction
{
    public function execute(
        AddBankReconciliationItemRequest $request,
        BankReconciliation $bankReconciliation,
    ): BankReconciliationItem {
        /** @var BankReconciliationItem $item */
        $item = $bankReconciliation->items()->create($request->validated());

        return $item;
    }
}
