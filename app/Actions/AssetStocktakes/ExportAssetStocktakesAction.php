<?php

namespace App\Actions\AssetStocktakes;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetStocktakeExport;

class ExportAssetStocktakesAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [
            'search' => $validated['search'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        return array_filter($filters, static fn (mixed $value): bool => ! is_null($value));
    }

    protected function filenamePrefix(): string
    {
        return 'asset_stocktakes';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetStocktakeExport($filters);
    }
}
