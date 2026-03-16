<?php

namespace App\Http\Requests\InventoryStocktakes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stocktakeId = $this->route('inventoryStocktake')->id ?? $this->route('id');

        return [
            'stocktake_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'unique:inventory_stocktakes,stocktake_number,' . $stocktakeId,
            ],
            'warehouse_id' => ['sometimes', 'required', 'exists:warehouses,id'],
            'stocktake_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,in_progress,completed,cancelled'],
            'product_category_id' => ['sometimes', 'nullable', 'exists:product_categories,id'],
            'notes' => ['sometimes', 'nullable', 'string'],

            'items' => ['sometimes', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'exists:units,id'],
            'items.*.system_quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
