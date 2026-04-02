<?php

namespace App\Actions\InventoryStocktakes;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\InventoryStocktakeExport;

class ExportInventoryStocktakesAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'warehouse_id' => null,
            'product_category_id' => null,
            'status' => null,
            'stocktake_date_from' => null,
            'stocktake_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'inventory_stocktakes';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new InventoryStocktakeExport($filters);
    }
}
