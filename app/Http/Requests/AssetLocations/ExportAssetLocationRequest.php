<?php

namespace App\Http\Requests\AssetLocations;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetLocationRequest extends FormRequest
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
            'sort_by' => ['nullable', 'string', 'in:code,name,branch,parent,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
