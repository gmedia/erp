<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\ArReceiptExport;

class ExportArReceiptsAction extends ConfiguredTransactionExportAction
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
            'payment_method' => null,
            'currency' => null,
            'receipt_date_from' => null,
            'receipt_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'ar_receipts';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new ArReceiptExport($filters);
    }
}
