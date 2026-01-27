<?php

namespace App\Http\Requests\CustomerCategories;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\CustomerCategory;

class StoreCustomerCategoryRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
