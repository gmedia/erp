<?php

namespace App\Actions\Warehouses;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\WarehouseExport;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export warehouses to Excel based on filters.
 */
class ExportWarehousesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return Warehouse::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new WarehouseExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'warehouses';
    }
}
