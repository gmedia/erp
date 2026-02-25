<?php

namespace App\Actions\Warehouses;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\Warehouse;

/**
 * Action to retrieve paginated warehouses with filtering and sorting.
 */
class IndexWarehousesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Warehouse::class;
    }
}
