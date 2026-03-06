<?php

namespace App\Domain\PurchaseOrders;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (! empty($filters['order_date_from'])) {
            $query->whereDate('order_date', '>=', $filters['order_date_from']);
        }

        if (! empty($filters['order_date_to'])) {
            $query->whereDate('order_date', '<=', $filters['order_date_to']);
        }

        if (! empty($filters['expected_delivery_date_from'])) {
            $query->whereDate('expected_delivery_date', '>=', $filters['expected_delivery_date_from']);
        }

        if (! empty($filters['expected_delivery_date_to'])) {
            $query->whereDate('expected_delivery_date', '<=', $filters['expected_delivery_date_to']);
        }

        if (! empty($filters['grand_total_min'])) {
            $query->where('grand_total', '>=', $filters['grand_total_min']);
        }

        if (! empty($filters['grand_total_max'])) {
            $query->where('grand_total', '<=', $filters['grand_total_max']);
        }
    }
}
