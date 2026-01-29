<?php

namespace App\Actions\ProductCategories;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\ProductCategory;

class IndexProductCategoriesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
