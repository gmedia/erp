<?php

namespace App\Actions\CustomerCategories;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\CustomerCategoryExport;
use App\Models\CustomerCategory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export customer categories to Excel based on filters.
 */
class ExportCustomerCategoriesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return CustomerCategory::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new CustomerCategoryExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'customer_categories';
    }
}
