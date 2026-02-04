<?php

namespace App\Http\Requests\AssetModels;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_category_id' => 'sometimes|required|exists:asset_categories,id',
            'manufacturer' => 'sometimes|nullable|string|max:255',
            'model_name' => 'sometimes|required|string|max:255',
            'specs' => 'sometimes|nullable|array',
        ];
    }
}
