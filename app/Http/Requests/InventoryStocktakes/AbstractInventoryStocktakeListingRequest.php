<?php

namespace App\Http\Requests\InventoryStocktakes;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractInventoryStocktakeListingRequest extends BaseListingRequest
{
    protected function inventoryStocktakeListingRules(string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'status' => ['nullable', 'string', 'in:draft,in_progress,completed,cancelled'],
            'stocktake_date_from' => ['nullable', 'date'],
            'stocktake_date_to' => ['nullable', 'date'],
            ...$this->listingSortRules($sortBy),
        ];
    }
}
