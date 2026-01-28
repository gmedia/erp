<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\SupplierCategory;

/**
 * Export class for supplier categories.
 */
class SupplierCategoryExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }
}
