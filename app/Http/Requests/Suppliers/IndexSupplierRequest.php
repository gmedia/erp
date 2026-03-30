<?php

namespace App\Http\Requests\Suppliers;

class IndexSupplierRequest extends AbstractSupplierListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->supplierListingRules('id,name,email,phone,address,branch,branch_id,category,category_id,status,created_at,updated_at'),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
