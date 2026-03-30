<?php

namespace App\Http\Requests\ApprovalFlows;

class StoreApprovalFlowRequest extends AbstractApprovalFlowRequest
{
    protected function codeRules(): string
    {
        return 'required|string|max:255|unique:approval_flows,code';
    }

    protected function includeStepIdRule(): bool
    {
        return false;
    }

    protected function useSometimes(): bool
    {
        return false;
    }
}
