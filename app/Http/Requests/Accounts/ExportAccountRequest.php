<?php

namespace App\Http\Requests\Accounts;

use App\Http\Requests\BaseListingRequest;

class ExportAccountRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'coa_version_id' => ['required', 'exists:coa_versions,id'],
                'type' => ['nullable', 'in:asset,liability,equity,revenue,expense'],
                'is_active' => ['nullable', 'boolean'],
            ],
        );
    }
}
