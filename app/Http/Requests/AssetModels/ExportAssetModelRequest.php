<?php

namespace App\Http\Requests\AssetModels;

use Illuminate\Foundation\Http\FormRequest;

class ExportAssetModelRequest extends FormRequest
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
            'sort_by' => ['nullable', 'string', 'in:model_name,manufacturer,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
