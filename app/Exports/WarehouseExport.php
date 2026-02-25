<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Warehouse;

/**
 * Export class for warehouses.
 */
class WarehouseExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Warehouse::class;
    }
}
