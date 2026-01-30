<?php

namespace App\Http\Requests\ProductCategories;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\ProductCategory;

class StoreProductCategoryRequest extends SimpleCrudStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'description' => ['nullable', 'string'],
        ]);
    }

    public function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
