<?php

namespace App\Http\Requests\StockAdjustments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adjustmentId = $this->route('stockAdjustment')->id ?? $this->route('id');

        return [
            'adjustment_number' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:stock_adjustments,adjustment_number,' . $adjustmentId],
            'warehouse_id' => ['sometimes', 'required', 'exists:warehouses,id'],
            'adjustment_date' => ['sometimes', 'required', 'date'],
            'adjustment_type' => ['sometimes', 'required', 'string', 'in:damage,expired,shrinkage,correction,stocktake_result,initial_stock,other'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,pending_approval,approved,cancelled'],
            'inventory_stocktake_id' => ['sometimes', 'nullable', 'exists:inventory_stocktakes,id'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'journal_entry_id' => ['sometimes', 'nullable', 'exists:journal_entries,id'],
            'approved_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'approved_at' => ['sometimes', 'nullable', 'date'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'exists:units,id'],
            'items.*.quantity_before' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity_adjusted' => ['required_with:items', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
        ];
    }
}
