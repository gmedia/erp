<?php

namespace App\Domain\GoodsReceipts;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['received_by'])) {
            $query->where('received_by', $filters['received_by']);
        }

        if (! empty($filters['receipt_date_from'])) {
            $query->whereDate('receipt_date', '>=', $filters['receipt_date_from']);
        }

        if (! empty($filters['receipt_date_to'])) {
            $query->whereDate('receipt_date', '<=', $filters['receipt_date_to']);
        }
    }
}
