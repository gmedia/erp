<?php

namespace App\Http\Requests\AssetCategories;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:asset_categories,code'],
            'name' => ['required', 'string', 'max:255'],
            'useful_life_months_default' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
