<?php

namespace App\Http\Requests\SecurityLogs;

class IndexSecurityLogRequest extends AbstractSecurityLogListingRequest
{
    public function rules(): array
    {
        return $this->securityLogListingRules([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
