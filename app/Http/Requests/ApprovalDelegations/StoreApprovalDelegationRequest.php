<?php

namespace App\Http\Requests\ApprovalDelegations;

class StoreApprovalDelegationRequest extends AbstractApprovalDelegationRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
