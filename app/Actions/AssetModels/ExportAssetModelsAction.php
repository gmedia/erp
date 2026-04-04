<?php

namespace App\Actions\AssetModels;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\AssetModelExport;

class ExportAssetModelsAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [
            'search' => $validated['search'] ?? null,
            'asset_category_id' => $validated['asset_category_id'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        return array_filter($filters);
    }

    protected function filenamePrefix(): string
    {
        return 'asset_models';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetModelExport($filters);
    }
}
