<?php

namespace App\Http\Requests\FiscalYears;

use App\Http\Requests\SimpleCrudListingRequest;

abstract class AbstractFiscalYearListingRequest extends SimpleCrudListingRequest
{
    protected function fiscalYearListingRules(?string $sortBy = null): array
    {
        return array_merge(
            $this->searchRules(),
            $sortBy ? $this->simpleCrudSortRulesByFields($sortBy) : $this->simpleCrudSortRules(),
            [
                'status' => ['nullable', 'string', 'in:open,closed,locked'],
            ],
        );
    }
}
