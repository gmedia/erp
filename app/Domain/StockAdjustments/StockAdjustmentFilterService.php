<?php

namespace App\Domain\StockAdjustments;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\StockAdjustment>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['adjustment_type'])) {
            $query->where('adjustment_type', $filters['adjustment_type']);
        }

        if (!empty($filters['inventory_stocktake_id'])) {
            $query->where('inventory_stocktake_id', $filters['inventory_stocktake_id']);
        }

        if (!empty($filters['adjustment_date_from'])) {
            $query->whereDate('adjustment_date', '>=', $filters['adjustment_date_from']);
        }

        if (!empty($filters['adjustment_date_to'])) {
            $query->whereDate('adjustment_date', '<=', $filters['adjustment_date_to']);
        }
    }
}
