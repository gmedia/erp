<?php

namespace App\Http\Requests\Customers;

use App\Http\Requests\BaseListingRequest;

class ExportCustomerRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch_id' => ['nullable', 'exists:branches,id'],
                'category_id' => ['nullable', 'exists:customer_categories,id'],
                'status' => ['nullable', 'string', 'in:active,inactive'],
            ],
            $this->listingSortRules('id,name,email,phone,address,branch_id,category_id,status,created_at,updated_at'),
        );
    }
}
