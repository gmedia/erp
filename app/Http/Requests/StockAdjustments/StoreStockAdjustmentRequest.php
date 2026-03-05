<?php

namespace App\Http\Requests\StockAdjustments;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_number' => ['nullable', 'string', 'max:255', 'unique:stock_adjustments,adjustment_number'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'string', 'in:damage,expired,shrinkage,correction,stocktake_result,initial_stock,other'],
            'status' => ['required', 'string', 'in:draft,pending_approval,approved,cancelled'],
            'inventory_stocktake_id' => ['nullable', 'exists:inventory_stocktakes,id'],
            'notes' => ['nullable', 'string'],
            'journal_entry_id' => ['nullable', 'exists:journal_entries,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],

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
