<?php

namespace App\Http\Requests\InventoryStocktakes;

class ExportInventoryStocktakeRequest extends AbstractInventoryStocktakeListingRequest
{
    public function rules(): array
    {
        return $this->inventoryStocktakeListingRules(
            'stocktake_number,warehouse_id,stocktake_date,status,product_category_id,created_at',
        );
    }
}
