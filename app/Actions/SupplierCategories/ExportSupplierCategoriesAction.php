<?php

namespace App\Actions\SupplierCategories;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\SupplierCategoryExport;
use App\Models\SupplierCategory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export supplier categories to Excel based on filters.
 */
class ExportSupplierCategoriesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new SupplierCategoryExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'supplier_categories';
    }
}
