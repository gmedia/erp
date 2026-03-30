<?php

namespace App\Http\Requests\ApprovalFlows;

use Illuminate\Validation\Rule;

class UpdateApprovalFlowRequest extends AbstractApprovalFlowRequest
{
    protected function codeRules(): array
    {
        return [
            'sometimes',
            'required',
            'string',
            'max:255',
            Rule::unique('approval_flows')->ignore($this->approval_flow),
        ];
    }

    protected function includeStepIdRule(): bool
    {
        return true;
    }

    protected function useSometimes(): bool
    {
        return true;
    }
}
