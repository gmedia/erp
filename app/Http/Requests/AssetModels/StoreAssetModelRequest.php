<?php

namespace App\Http\Requests\AssetModels;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_category_id' => 'required|exists:asset_categories,id',
            'manufacturer' => 'nullable|string|max:255',
            'model_name' => 'required|string|max:255',
            'specs' => 'nullable|array',
        ];
    }
}
