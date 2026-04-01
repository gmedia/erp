<?php

namespace App\Http\Requests\ApprovalAuditTrail;

class IndexApprovalAuditTrailRequest extends AbstractApprovalAuditTrailListingRequest
{
    public function rules(): array
    {
        return $this->approvalAuditTrailListingRules([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
