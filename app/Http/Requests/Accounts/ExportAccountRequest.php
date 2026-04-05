<?php

namespace App\Http\Requests\Accounts;

class ExportAccountRequest extends AbstractAccountListingRequest
{
    public function rules(): array
    {
        return $this->accountListingRules(['required', 'exists:coa_versions,id']);
    }
}
