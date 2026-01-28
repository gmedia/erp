<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\CustomerCategory;

/**
 * Export class for customer categories.
 */
class CustomerCategoryExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }
}
