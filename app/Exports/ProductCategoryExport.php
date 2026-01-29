<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\ProductCategory;

class ProductCategoryExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
