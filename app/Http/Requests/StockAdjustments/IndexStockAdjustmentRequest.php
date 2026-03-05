<?php

namespace App\Http\Requests\StockAdjustments;

use Illuminate\Foundation\Http\FormRequest;

class IndexStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,cancelled'],
            'adjustment_type' => ['nullable', 'string', 'in:damage,expired,shrinkage,correction,stocktake_result,initial_stock,other'],
            'inventory_stocktake_id' => ['nullable', 'exists:inventory_stocktakes,id'],
            'adjustment_date_from' => ['nullable', 'date'],
            'adjustment_date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'string', 'in:id,adjustment_number,warehouse_id,adjustment_date,adjustment_type,status,inventory_stocktake_id,journal_entry_id,approved_by,approved_at,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
