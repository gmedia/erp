<?php

namespace App\Actions\AssetMaintenances;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetMaintenanceExport;

class ExportAssetMaintenancesAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
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
        return 'asset-maintenances';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetMaintenanceExport($filters);
    }
}
