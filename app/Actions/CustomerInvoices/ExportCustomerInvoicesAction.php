<?php

namespace App\Actions\CustomerInvoices;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\CustomerInvoiceExport;

class ExportCustomerInvoicesAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'customer' => null,
            'branch' => null,
            'status' => null,
            'currency' => null,
            'invoice_date_from' => null,
            'invoice_date_to' => null,
            'due_date_from' => null,
            'due_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'customer_invoices';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new CustomerInvoiceExport($filters);
    }
}
