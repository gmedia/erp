<?php

namespace App\Http\Requests\ApprovalDelegations;

class StoreApprovalDelegationRequest extends AbstractApprovalDelegationRequest
{
    protected function useSometimesRules(): bool
    {
        return false;
    }
}
