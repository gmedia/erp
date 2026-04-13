<?php

namespace App\Http\Requests\PurchaseRequests;

class StorePurchaseRequestRequest extends AbstractPurchaseRequestRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function requiredDateRules(): array
    {
        return ['nullable', 'date', 'after_or_equal:request_date'];
    }
}
