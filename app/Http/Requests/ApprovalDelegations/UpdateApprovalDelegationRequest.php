<?php

namespace App\Http\Requests\ApprovalDelegations;

class UpdateApprovalDelegationRequest extends AbstractApprovalDelegationRequest
{
    protected function useSometimesRules(): bool
    {
        return true;
    }
}
