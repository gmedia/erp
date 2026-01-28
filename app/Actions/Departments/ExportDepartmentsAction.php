<?php

namespace App\Actions\Departments;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\DepartmentExport;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export departments to Excel based on filters.
 */
class ExportDepartmentsAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new DepartmentExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'departments';
    }
}
