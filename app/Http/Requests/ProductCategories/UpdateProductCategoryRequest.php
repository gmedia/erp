<?php

namespace App\Http\Requests\ProductCategories;

use App\Http\Requests\SimpleCrudUpdateRequest;

class UpdateProductCategoryRequest extends SimpleCrudUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'description' => ['nullable', 'string'],
        ]);
    }
}
