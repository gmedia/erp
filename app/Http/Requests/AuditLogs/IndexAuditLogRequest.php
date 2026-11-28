<?php

namespace App\Http\Requests\AuditLogs;

class IndexAuditLogRequest extends AbstractAuditLogListingRequest
{
    public function rules(): array
    {
        return $this->auditLogListingRules();
    }
}
