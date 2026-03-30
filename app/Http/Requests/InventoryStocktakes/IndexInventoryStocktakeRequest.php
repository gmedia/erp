<?php

namespace App\Http\Requests\InventoryStocktakes;

class IndexInventoryStocktakeRequest extends AbstractInventoryStocktakeListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->inventoryStocktakeListingRules(
                'id,stocktake_number,warehouse_id,stocktake_date,status,product_category_id,created_at,updated_at',
            ),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
