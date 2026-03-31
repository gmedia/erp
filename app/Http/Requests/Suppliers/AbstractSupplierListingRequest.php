<?php

namespace App\Http\Requests\Suppliers;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractSupplierListingRequest extends BaseListingRequest
{
    protected function supplierListingRules(string $sortBy, bool $includeStringInSortDirection = true): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category_id' => ['nullable', 'exists:supplier_categories,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            ...$this->listingSortRules($sortBy, $includeStringInSortDirection),
        ];
    }
}
