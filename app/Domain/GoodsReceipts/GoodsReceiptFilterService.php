<?php

namespace App\Domain\GoodsReceipts;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'purchase_order_id' => 'purchase_order_id',
            'warehouse_id' => 'warehouse_id',
            'status' => 'status',
            'received_by' => 'received_by',
        ]);

        $this->applyDateRanges($query, $filters, [
            'receipt_date' => ['from' => 'receipt_date_from', 'to' => 'receipt_date_to'],
        ]);
    }
}
