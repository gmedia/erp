<?php

namespace App\Actions\AssetLocations;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\AssetLocationExport;

class ExportAssetLocationsAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'branch_id' => null,
            'parent_id' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'asset_locations';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AssetLocationExport($filters);
    }
}
