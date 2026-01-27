<?php

namespace App\Http\Requests\SupplierCategories;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\SupplierCategory;

class UpdateSupplierCategoryRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
