<?php

namespace App\Http\Requests;

abstract class SimpleCrudListingRequest extends BaseListingRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function simpleCrudSortRules(): array
    {
        return $this->listingSortRules('id,name,created_at,updated_at', false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function simpleCrudSortRulesByFields(string $sortBy): array
    {
        return $this->listingSortRules($sortBy, false);
    }
}
