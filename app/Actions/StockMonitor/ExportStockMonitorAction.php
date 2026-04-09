<?php

namespace App\Actions\StockMonitor;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\StockMonitorExport;

class ExportStockMonitorAction extends ConfiguredFormattedExportAction
{
    protected function filenamePrefix(): string
    {
        return 'stock_monitor';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new StockMonitorExport($filters);
    }
}
