<?php

namespace App\Http\Requests\InventoryStocktakes;

use App\Http\Requests\AuthorizedFormRequest;

abstract class AbstractInventoryStocktakeRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function inventoryStocktakeBaseRules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'stocktake_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:draft,in_progress,completed,cancelled'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function inventoryStocktakeItemRules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'exists:units,id'],
            'items.*.system_quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
