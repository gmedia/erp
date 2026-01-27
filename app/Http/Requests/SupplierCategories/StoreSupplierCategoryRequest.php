<?php

namespace App\Http\Requests\SupplierCategories;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\SupplierCategory;

class StoreSupplierCategoryRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
