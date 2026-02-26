<?php

namespace App\Http\Requests\InventoryStocktakes;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stocktake_number' => ['nullable', 'string', 'max:255', 'unique:inventory_stocktakes,stocktake_number'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'stocktake_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:draft,in_progress,completed,cancelled'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'notes' => ['nullable', 'string'],

            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'exists:units,id'],
            'items.*.system_quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}

