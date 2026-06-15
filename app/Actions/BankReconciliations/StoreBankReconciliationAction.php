<?php

namespace App\Actions\BankReconciliations;

use App\Http\Requests\BankReconciliations\StoreBankReconciliationRequest;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\DB;

class StoreBankReconciliationAction
{
    public function execute(StoreBankReconciliationRequest $request): BankReconciliation
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['status'] ??= 'draft';
        $data['reconciled_balance'] ??= 0;
        $data['difference'] ??= (float) $data['statement_balance'] - (float) $data['book_balance'];
        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $items): BankReconciliation {
            $bankReconciliation = BankReconciliation::create($data);

            foreach ($items as $item) {
                $bankReconciliation->items()->create($item);
            }

            return $bankReconciliation;
        });
    }
}
