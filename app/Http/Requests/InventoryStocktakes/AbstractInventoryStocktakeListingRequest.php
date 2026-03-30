<?php

namespace App\Http\Requests\InventoryStocktakes;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractInventoryStocktakeListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function inventoryStocktakeListingRules(string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'status' => ['nullable', 'string', 'in:draft,in_progress,completed,cancelled'],
            'stocktake_date_from' => ['nullable', 'date'],
            'stocktake_date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}