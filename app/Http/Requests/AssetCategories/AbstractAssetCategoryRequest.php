<?php

namespace App\Http\Requests\AssetCategories;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractAssetCategoryRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                $this->codeUniqueRule(),
            ],
            'name' => ['required', 'string', 'max:255'],
            'useful_life_months_default' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function codeUniqueRule(): Rule|string
    {
        if (! $this->usesIgnoreRule()) {
            return 'unique:asset_categories,code';
        }

        return Rule::unique('asset_categories', 'code')->ignore($this->route('asset_category'));
    }

    abstract protected function usesIgnoreRule(): bool;
}
