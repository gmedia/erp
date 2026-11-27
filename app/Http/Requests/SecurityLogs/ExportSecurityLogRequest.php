<?php

namespace App\Http\Requests\SecurityLogs;

class ExportSecurityLogRequest extends AbstractSecurityLogListingRequest
{
    public function rules(): array
    {
        return $this->securityLogListingRules();
    }
}
