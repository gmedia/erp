<?php

namespace App\Http\Requests\Accounts;

use App\Http\Requests\BaseListingRequest;

class IndexAccountRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            [
                'coa_version_id' => ['nullable', 'exists:coa_versions,id'],
            ],
            $this->searchRules(),
            [
                'type' => ['nullable', 'in:asset,liability,equity,revenue,expense'],
                'is_active' => ['nullable', 'boolean'],
            ],
            $this->listingSortRules('code,name,type,level', true, 'sort_order'),
            $this->perPageRules(),
        );
    }
}
