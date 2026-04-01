<?php

namespace App\Domain\PurchaseOrders;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'supplier_id' => 'supplier_id',
                'warehouse_id' => 'warehouse_id',
                'status' => 'status',
                'currency' => 'currency',
            ],
            [
                'order_date' => ['from' => 'order_date_from', 'to' => 'order_date_to'],
                'expected_delivery_date' => ['from' => 'expected_delivery_date_from', 'to' => 'expected_delivery_date_to'],
            ],
            [
                'grand_total' => ['min' => 'grand_total_min', 'max' => 'grand_total_max'],
            ],
        );
    }
}
