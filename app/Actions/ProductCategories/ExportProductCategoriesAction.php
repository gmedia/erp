<?php

namespace App\Actions\ProductCategories;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\ProductCategoryExport;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

class ExportProductCategoriesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new ProductCategoryExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'product_categories';
    }
}
