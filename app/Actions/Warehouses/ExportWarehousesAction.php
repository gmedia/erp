<?php

namespace App\Actions\Warehouses;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\WarehouseExport;

/**
 * Action to export warehouses to Excel based on filters.
 */
class ExportWarehousesAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'branch_id' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'warehouses';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new WarehouseExport($filters);
    }
}
