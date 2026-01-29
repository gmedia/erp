<?php

namespace App\Http\Requests\ProductCategories;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\ProductCategory;

class UpdateProductCategoryRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
