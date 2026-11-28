<?php

namespace App\Http\Requests\AuditLogs;

class ExportAuditLogRequest extends AbstractAuditLogListingRequest
{
    public function rules(): array
    {
        return $this->auditLogListingRules();
    }
}
