<?php

namespace App\Http\Requests\PurchaseRequests;

class UpdatePurchaseRequestRequest extends AbstractPurchaseRequestRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
