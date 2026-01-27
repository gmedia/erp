<?php

namespace App\Http\Requests\CustomerCategories;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\CustomerCategory;

class UpdateCustomerCategoryRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
