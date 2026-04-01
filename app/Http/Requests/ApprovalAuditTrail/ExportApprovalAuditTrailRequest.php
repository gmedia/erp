<?php

namespace App\Http\Requests\ApprovalAuditTrail;

class ExportApprovalAuditTrailRequest extends AbstractApprovalAuditTrailListingRequest
{
    public function rules(): array
    {
        return $this->approvalAuditTrailListingRules();
    }
}
