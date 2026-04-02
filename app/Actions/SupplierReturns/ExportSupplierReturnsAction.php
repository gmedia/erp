<?php

namespace App\Actions\SupplierReturns;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\SupplierReturnExport;

class ExportSupplierReturnsAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'purchase_order' => null,
            'goods_receipt' => null,
            'supplier' => null,
            'warehouse' => null,
            'reason' => null,
            'status' => null,
            'return_date_from' => null,
            'return_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'supplier_returns';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new SupplierReturnExport($filters);
    }
}
