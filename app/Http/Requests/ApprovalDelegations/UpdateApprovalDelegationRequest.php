<?php

namespace App\Http\Requests\ApprovalDelegations;

class UpdateApprovalDelegationRequest extends AbstractApprovalDelegationRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
