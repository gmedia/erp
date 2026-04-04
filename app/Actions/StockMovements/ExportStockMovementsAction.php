<?php

namespace App\Actions\StockMovements;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\StockMovementsExport;

class ExportStockMovementsAction extends ConfiguredFormattedExportAction
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
