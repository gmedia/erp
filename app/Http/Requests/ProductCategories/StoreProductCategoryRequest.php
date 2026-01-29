<?php

namespace App\Http\Requests\ProductCategories;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\ProductCategory;

class StoreProductCategoryRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
