<?php

namespace App\Http\Requests\AssetModels;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssetModelRequest extends FormRequest
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
            'sort_by' => ['nullable', 'string', 'in:id,model_name,manufacturer,asset_category_id,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
