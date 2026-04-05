<?php

namespace App\Http\Requests\ApprovalFlows;

class IndexApprovalFlowRequest extends AbstractApprovalFlowListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->approvalFlowListingRules('id,name,code,approvable_type,is_active,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
