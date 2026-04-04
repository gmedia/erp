<?php

namespace App\Http\Requests\InventoryStocktakes;

class StoreInventoryStocktakeRequest extends AbstractInventoryStocktakeRequest
{
    public function rules(): array
    {
        return array_merge(
            [
                'stocktake_number' => ['nullable', 'string', 'max:255', 'unique:inventory_stocktakes,stocktake_number'],
            ],
            $this->inventoryStocktakeBaseRules(),
            $this->inventoryStocktakeItemRules(),
        );
    }
}
