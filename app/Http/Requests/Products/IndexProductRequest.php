<?php

namespace App\Http\Requests\Products;

class IndexProductRequest extends AbstractProductListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->productListingRules(
                'id,code,name,type,category,category_id,unit_id,cost,selling_price,status,created_at,updated_at',
            ),
            [
                'is_manufactured' => ['nullable', 'boolean'],
                'is_purchasable' => ['nullable', 'boolean'],
                'is_sellable' => ['nullable', 'boolean'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
