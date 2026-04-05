<?php

namespace App\Http\Requests\ApprovalFlows;

class ExportApprovalFlowRequest extends AbstractApprovalFlowListingRequest
{
    public function rules(): array
    {
        return $this->approvalFlowListingRules('name,code,approvable_type,is_active,created_at');
    }
}
