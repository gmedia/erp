<?php

namespace App\Actions\ApPayments;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\ApPaymentExport;

class ExportApPaymentsAction extends ConfiguredTransactionExportAction
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
            'payment_method' => null,
            'currency' => null,
            'payment_date_from' => null,
            'payment_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'ap_payments';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new ApPaymentExport($filters);
    }
}
