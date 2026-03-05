<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexStockAdjustmentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'adjustment_type' => ['nullable', 'string', Rule::in(['damage', 'expired', 'shrinkage', 'correction', 'stocktake_result', 'initial_stock', 'other'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending_approval', 'approved', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'adjustment_date',
                    'adjustment_type',
                    'status',
                    'warehouse_name',
                    'branch_name',
                    'total_quantity_adjusted',
                    'total_adjustment_value',
                    'adjustment_count',
                ]),
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ];
    }
}
