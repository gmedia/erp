<?php

namespace App\Domain\StockTransfers;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class StockTransferFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\StockTransfer>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $filters['from_warehouse_id']);
        }

        if (! empty($filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $filters['to_warehouse_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['transfer_date_from'])) {
            $query->whereDate('transfer_date', '>=', $filters['transfer_date_from']);
        }

        if (! empty($filters['transfer_date_to'])) {
            $query->whereDate('transfer_date', '<=', $filters['transfer_date_to']);
        }

        if (! empty($filters['expected_arrival_date_from'])) {
            $query->whereDate('expected_arrival_date', '>=', $filters['expected_arrival_date_from']);
        }

        if (! empty($filters['expected_arrival_date_to'])) {
            $query->whereDate('expected_arrival_date', '<=', $filters['expected_arrival_date_to']);
        }
    }
}
