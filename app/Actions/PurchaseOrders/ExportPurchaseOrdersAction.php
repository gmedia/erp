<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\PurchaseOrderExport;

class ExportPurchaseOrdersAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'supplier' => null,
            'warehouse' => null,
            'status' => null,
            'currency' => null,
            'order_date_from' => null,
            'order_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'purchase_orders';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new PurchaseOrderExport($filters);
    }
}
