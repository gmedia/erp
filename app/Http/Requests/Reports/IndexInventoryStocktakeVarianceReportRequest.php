<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInventoryStocktakeVarianceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'inventory_stocktake_id' => ['nullable', 'integer', 'exists:inventory_stocktakes,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'result' => ['nullable', 'string', Rule::in(['surplus', 'deficit'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'stocktake_number',
                    'stocktake_date',
                    'product_name',
                    'product_code',
                    'category_name',
                    'warehouse_name',
                    'branch_name',
                    'system_quantity',
                    'counted_quantity',
                    'variance',
                    'result',
                    'counted_at',
                    'counted_by_name',
                ]),
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ];
    }
}
