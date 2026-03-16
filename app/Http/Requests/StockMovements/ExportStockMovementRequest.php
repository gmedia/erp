<?php

namespace App\Http\Requests\StockMovements;

use Illuminate\Foundation\Http\FormRequest;

class ExportStockMovementRequest extends FormRequest
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
            'movement_type' => [
                'nullable',
                'string',
                'in:goods_receipt,supplier_return,transfer_out,transfer_in,adjustment_in,adjustment_out,'
                    . 'production_consume,production_output,sales,sales_return',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => [
                'nullable',
                'string',
                'in:moved_at,movement_type,quantity_in,quantity_out,balance_after,unit_cost,average_cost_after,'
                    . 'reference_number,product_name,warehouse_name,created_by',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
        ];
    }
}
