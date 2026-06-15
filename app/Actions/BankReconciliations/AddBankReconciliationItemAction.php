<?php

namespace App\Actions\BankReconciliations;

use App\Http\Requests\BankReconciliations\AddBankReconciliationItemRequest;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Illuminate\Support\Facades\DB;

class AddBankReconciliationItemAction
{
    public function execute(
        AddBankReconciliationItemRequest $request,
        BankReconciliation $bankReconciliation,
    ): BankReconciliationItem {
        return DB::transaction(function () use ($request, $bankReconciliation): BankReconciliationItem {
            /** @var BankReconciliationItem $item */
            $item = $bankReconciliation->items()->create($request->validated());

            $bankReconciliation->recalculateBalances();

            return $item;
        });
    }
}
