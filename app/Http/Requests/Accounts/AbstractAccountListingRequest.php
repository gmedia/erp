<?php

namespace App\Http\Requests\Accounts;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAccountListingRequest extends BaseListingRequest
{
    /**
     * @param  array<int, string>  $coaVersionRules
     * @return array<string, array<int, string>>
     */
    protected function accountListingRules(array $coaVersionRules): array
    {
        return array_merge(
            [
                'coa_version_id' => $coaVersionRules,
            ],
            $this->searchRules(),
            [
                'type' => ['nullable', 'in:asset,liability,equity,revenue,expense'],
                'is_active' => ['nullable', 'boolean'],
            ],
        );
    }
}
