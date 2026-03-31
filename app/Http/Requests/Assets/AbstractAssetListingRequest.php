<?php

namespace App\Http\Requests\Assets;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetListingRequest extends BaseListingRequest
{
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
            ...$this->listingSortRules($sortBy),
        ];
    }
}
