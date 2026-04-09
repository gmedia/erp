<?php

namespace App\Actions\Customers;

use App\Actions\Concerns\ConfiguredPartyExportAction;
use App\Exports\CustomerExport;

class ExportCustomersAction extends ConfiguredPartyExportAction
{
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
