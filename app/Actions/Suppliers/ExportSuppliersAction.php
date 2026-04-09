<?php

namespace App\Actions\Suppliers;

use App\Actions\Concerns\ConfiguredPartyExportAction;
use App\Exports\SupplierExport;

class ExportSuppliersAction extends ConfiguredPartyExportAction
{
    protected function filenamePrefix(): string
    {
        return 'suppliers';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new SupplierExport($filters);
    }
}
