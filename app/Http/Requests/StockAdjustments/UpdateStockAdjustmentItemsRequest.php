<?php

namespace App\Http\Requests\StockAdjustments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockAdjustmentItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.quantity_before' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_adjusted' => ['required', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }
}
