<?php

namespace App\Domain\SupplierReturns;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class SupplierReturnFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'purchase_order_id' => 'purchase_order_id',
            'goods_receipt_id' => 'goods_receipt_id',
            'supplier_id' => 'supplier_id',
            'warehouse_id' => 'warehouse_id',
            'reason' => 'reason',
            'status' => 'status',
        ]);

        $this->applyDateRanges($query, $filters, [
            'return_date' => ['from' => 'return_date_from', 'to' => 'return_date_to'],
        ]);
    }
}
