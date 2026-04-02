<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\PurchaseRequestExport;

class ExportPurchaseRequestsAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'branch' => null,
            'department' => null,
            'requested_by' => null,
            'priority' => null,
            'status' => null,
            'request_date_from' => null,
            'request_date_to' => null,
            'required_date_from' => null,
            'required_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'purchase_requests';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new PurchaseRequestExport($filters);
    }
}
