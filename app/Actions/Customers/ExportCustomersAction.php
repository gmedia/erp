<?php

namespace App\Actions\Customers;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\CustomerExport;

class ExportCustomersAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'branch_id' => null,
            'category_id' => null,
            'status' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'customers';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new CustomerExport($filters);
    }
}
