<?php

namespace App\Actions\Employees;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\EmployeeExport;

/**
 * Action to export employees to Excel based on filters
 */
class ExportEmployeesAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'department_id' => null,
            'position_id' => null,
            'branch_id' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'employees';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new EmployeeExport($filters);
    }
}
