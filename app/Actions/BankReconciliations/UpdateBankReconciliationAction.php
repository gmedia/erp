<?php

namespace App\Actions\BankReconciliations;

use App\Http\Requests\BankReconciliations\UpdateBankReconciliationRequest;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;

class UpdateBankReconciliationAction
{
    public function execute(
        UpdateBankReconciliationRequest $request,
        BankReconciliation $bankReconciliation,
    ): BankReconciliation {
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);

        return DB::transaction(function () use ($data, $items, $bankReconciliation): BankReconciliation {
            $bankReconciliation->update($data);

            if (is_array($items)) {
                $bankReconciliation->items()->delete();

                foreach ($items as $item) {
                    $bankReconciliation->items()->create($item);
                }
            }

            return $bankReconciliation->refresh();
        });
    }
}
