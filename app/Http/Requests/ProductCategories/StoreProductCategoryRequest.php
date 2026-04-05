<?php

namespace App\Http\Requests\ProductCategories;

use App\Http\Requests\SimpleCrudStoreRequest;

class StoreProductCategoryRequest extends SimpleCrudStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'description' => ['nullable', 'string'],
        ]);
    }
}
