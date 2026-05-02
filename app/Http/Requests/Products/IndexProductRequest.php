<?php

namespace App\Http\Requests\Products;

class IndexProductRequest extends AbstractProductListingRequest
{
    public function rules(): array
    {
        return $this->productListingRules(
            'id,code,name,type,category,product_category_id,unit_id,cost,selling_price,status,created_at,updated_at',
        );
    }
}
