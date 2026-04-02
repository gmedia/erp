<?php

namespace App\Actions\StockTransfers;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\StockTransferExport;

class ExportStockTransfersAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'from_warehouse_id' => null,
            'to_warehouse_id' => null,
            'status' => null,
            'transfer_date_from' => null,
            'transfer_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'stock_transfers';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new StockTransferExport($filters);
    }
}
