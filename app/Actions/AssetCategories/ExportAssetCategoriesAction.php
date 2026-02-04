<?php

namespace App\Actions\AssetCategories;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\AssetCategoryExport;
use App\Models\AssetCategory;

class ExportAssetCategoriesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }

    protected function getExportInstance(array $filters, ?\Illuminate\Database\Eloquent\Builder $query): \Maatwebsite\Excel\Concerns\FromQuery
    {
        return new AssetCategoryExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'asset_categories';
    }
}
