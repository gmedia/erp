<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportInventoryValuationReportRequest extends FormRequest
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
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'product_name',
                    'warehouse_name',
                    'branch_name',
                    'category_name',
                    'quantity_on_hand',
                    'average_cost',
                    'stock_value',
                    'moved_at',
                ]),
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'format' => ['nullable', 'string', Rule::in(['xlsx', 'csv'])],
        ];
    }
}
