<?php

namespace App\Http\Requests\Products;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractProductListingRequest extends BaseListingRequest
{
    protected function productListingRules(string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'type' => ['nullable', 'in:raw_material,work_in_progress,finished_good,purchased_good,service'],
            'status' => ['nullable', 'in:active,inactive,discontinued'],
            'billing_model' => ['nullable', 'in:one_time,subscription,both'],
            ...$this->listingSortRules($sortBy),
        ];
    }
}
