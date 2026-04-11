<?php

namespace App\Actions\AssetStocktakes;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetStocktakeVarianceExport;

class ExportAssetStocktakeVariancesAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [
            'asset_stocktake_id' => $validated['asset_stocktake_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'result' => $validated['result'] ?? null,
            'search' => $validated['search'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'checked_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        return array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function buildFilename(): string
    {
        return $this->filenamePrefix() . '_' . now()->format($this->timestampFormat()) . '.xlsx';
    }

    protected function timestampFormat(): string
    {
        return 'Ymd_His';
    }

    protected function filenamePrefix(): string
    {
        return 'asset_stocktake_variances';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetStocktakeVarianceExport($filters);
    }
}
