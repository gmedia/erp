<?php

namespace App\Actions\StockMonitor;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\StockMonitorExport;

class ExportStockMonitorAction extends ConfiguredFormattedExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

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
