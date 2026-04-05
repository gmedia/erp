<?php

namespace App\Http\Requests\Accounts;

class IndexAccountRequest extends AbstractAccountListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->accountListingRules(['nullable', 'exists:coa_versions,id']),
            $this->listingSortRules('code,name,type,level', true, 'sort_order'),
            $this->perPageRules(),
        );
    }
}
