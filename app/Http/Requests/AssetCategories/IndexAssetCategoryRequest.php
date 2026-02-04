<?php

namespace App\Http\Requests\AssetCategories;

use App\Http\Requests\SimpleCrudIndexRequest;

class IndexAssetCategoryRequest extends SimpleCrudIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'sort_by' => ['nullable', 'string', 'in:id,code,name,useful_life_months_default,created_at,updated_at'],
        ]);
    }
}
