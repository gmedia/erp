<?php

namespace App\Domain\SupplierReturns;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class SupplierReturnFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (! empty($filters['goods_receipt_id'])) {
            $query->where('goods_receipt_id', $filters['goods_receipt_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['return_date_from'])) {
            $query->whereDate('return_date', '>=', $filters['return_date_from']);
        }

        if (! empty($filters['return_date_to'])) {
            $query->whereDate('return_date', '<=', $filters['return_date_to']);
        }
    }
}
