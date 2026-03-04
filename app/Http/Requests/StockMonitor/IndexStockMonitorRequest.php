<?php

namespace App\Http\Requests\StockMonitor;

use Illuminate\Foundation\Http\FormRequest;

class IndexStockMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:product_name,warehouse_name,category_name,quantity_on_hand,average_cost,stock_value,moved_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
