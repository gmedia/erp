<?php

namespace App\Actions\StockAdjustments;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\StockAdjustmentExport;

class ExportStockAdjustmentsAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'warehouse_id' => null,
            'status' => null,
            'adjustment_type' => null,
            'inventory_stocktake_id' => null,
            'adjustment_date_from' => null,
            'adjustment_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'stock_adjustments';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new StockAdjustmentExport($filters);
    }
}
