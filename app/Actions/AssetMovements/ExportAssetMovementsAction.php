<?php

namespace App\Actions\AssetMovements;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetMovementExport;

class ExportAssetMovementsAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return array_filter($validated);
    }

    protected function filenameDelimiter(): string
    {
        return '-';
    }

    protected function timestampFormat(): string
    {
        return 'Y-m-d-H-i-s';
    }

    protected function filenamePrefix(): string
    {
        return 'asset-movements';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetMovementExport($filters);
    }
}
