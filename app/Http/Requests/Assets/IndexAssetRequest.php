<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            'asset_model_id' => ['nullable', 'exists:asset_models,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'asset_location_id' => ['nullable', 'exists:asset_locations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'status' => ['nullable', 'string', 'in:draft,active,maintenance,disposed,lost'],
            'condition' => ['nullable', 'string', 'in:good,needs_repair,damaged'],
            'sort_by' => ['nullable', 'string', 'in:id,asset_code,name,purchase_date,purchase_cost,status,created_at,category,branch,location,department,employee,supplier'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
