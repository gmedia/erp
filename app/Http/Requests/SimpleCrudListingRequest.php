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
}
