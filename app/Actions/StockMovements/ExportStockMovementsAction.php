<?php

namespace App\Actions\StockMovements;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\StockMovementsExport;

class ExportStockMovementsAction extends ConfiguredFormattedExportAction
{
    protected function filenamePrefix(): string
    {
        return 'stock_movements';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new StockMovementsExport($filters);
    }
}
