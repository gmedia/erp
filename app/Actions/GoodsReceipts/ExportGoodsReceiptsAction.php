<?php

namespace App\Actions\GoodsReceipts;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\GoodsReceiptExport;

class ExportGoodsReceiptsAction extends ConfiguredTransactionExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'purchase_order' => null,
            'warehouse' => null,
            'status' => null,
            'received_by' => null,
            'receipt_date_from' => null,
            'receipt_date_to' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'goods_receipts';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new GoodsReceiptExport($filters);
    }
}
