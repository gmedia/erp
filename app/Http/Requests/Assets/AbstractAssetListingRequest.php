<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function assetListingRules(string $sortBy): array
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
            'sort_by' => ['nullable', 'string', 'in:' . $sortBy],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
