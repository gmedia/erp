<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Validation\Rule;

class UpdatePurchaseRequestRequest extends AbstractPurchaseRequestRequest
{
    protected function prNumberUniqueRule(): object
    {
        return Rule::unique('purchase_requests', 'pr_number')->ignore($this->route('purchaseRequest')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }
}
