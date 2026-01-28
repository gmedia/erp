<?php

namespace App\Actions\CustomerCategories;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\CustomerCategory;

/**
 * Action to retrieve paginated customer categories with filtering and sorting.
 */
class IndexCustomerCategoriesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
