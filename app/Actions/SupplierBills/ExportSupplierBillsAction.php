<?php

namespace App\Actions\SupplierBills;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\SupplierBillExport;

class ExportSupplierBillsAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'supplier' => null,
            'branch' => null,
            'status' => null,
            'currency' => null,
            'bill_date_from' => null,
            'bill_date_to' => null,
            'due_date_from' => null,
            'due_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'supplier_bills';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new SupplierBillExport($filters);
    }
}
