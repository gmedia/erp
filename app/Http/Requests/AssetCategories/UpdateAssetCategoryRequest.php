<?php

namespace App\Http\Requests\AssetCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'code')->ignore($this->route('asset_category')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'useful_life_months_default' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
