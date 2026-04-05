<?php

namespace App\Http\Requests\Customers;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractCustomerListingRequest extends BaseListingRequest
{
    protected function customerListingRules(string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category_id' => ['nullable', 'exists:customer_categories,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            ...$this->listingSortRules($sortBy),
        ];
    }
}
