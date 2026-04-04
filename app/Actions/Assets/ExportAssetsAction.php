<?php

namespace App\Actions\Assets;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetExport;

class ExportAssetsAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return array_filter($validated);
    }

    protected function includeUlidInFilename(): bool
    {
        return true;
    }

    protected function filenamePrefix(): string
    {
        return 'assets';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetExport($filters);
    }
}
