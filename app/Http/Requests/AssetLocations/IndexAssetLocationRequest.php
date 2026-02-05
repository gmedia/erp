<?php

namespace App\Http\Requests\AssetLocations;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'parent_id' => ['nullable', 'exists:asset_locations,id'],
            'sort_by' => ['nullable', 'string', 'in:id,code,name,branch_id,parent_id,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
