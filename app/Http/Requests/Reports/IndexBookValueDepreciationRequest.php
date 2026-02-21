<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookValueDepreciationRequest extends FormRequest
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
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'asset_code',
                    'name',
                    'purchase_date',
                    'purchase_cost',
                    'book_value',
                    'accumulated_depreciation'
                ]),
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
