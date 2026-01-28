<?php

namespace App\Actions\SupplierCategories;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\SupplierCategory;

/**
 * Action to retrieve paginated supplier categories with filtering and sorting.
 */
class IndexSupplierCategoriesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
