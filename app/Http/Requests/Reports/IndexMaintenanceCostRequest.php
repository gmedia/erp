<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMaintenanceCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'maintenance_type' => ['nullable', 'string', Rule::in(['preventive', 'corrective', 'calibration', 'other'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'maintenance_type',
                    'status',
                    'scheduled_at',
                    'performed_at',
                    'cost',
                    'asset_code',
                    'asset_name',
                    'supplier_name'
                ]),
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
