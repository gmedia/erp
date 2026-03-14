<?php

namespace App\Actions\AssetCategories;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\AssetCategoryExport;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

class ExportAssetCategoriesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new AssetCategoryExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'asset_categories';
    }
}
