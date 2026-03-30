<?php

namespace App\Http\Requests\Products;

class ExportProductRequest extends AbstractProductListingRequest
{
    public function rules(): array
    {
        return $this->productListingRules('code,name,type,cost,selling_price,status,created_at');
    }
}
