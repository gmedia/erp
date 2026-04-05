<?php

namespace App\Http\Requests\CoaVersions;

use App\Http\Requests\SimpleCrudListingRequest;

abstract class AbstractCoaVersionListingRequest extends SimpleCrudListingRequest
{
    protected function coaVersionListingRules(?string $sortBy = null): array
    {
        return array_merge(
            $this->searchRules(),
            $sortBy ? $this->simpleCrudSortRulesByFields($sortBy) : $this->simpleCrudSortRules(),
            [
                'status' => ['nullable', 'string', 'in:draft,active,archived'],
                'fiscal_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id'],
            ],
        );
    }
}
