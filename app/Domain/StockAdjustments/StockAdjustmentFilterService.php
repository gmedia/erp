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
        $this->applyExactFilters($query, $filters, [
            'warehouse_id' => 'warehouse_id',
            'status' => 'status',
            'adjustment_type' => 'adjustment_type',
            'inventory_stocktake_id' => 'inventory_stocktake_id',
        ]);

        $this->applyDateRanges($query, $filters, [
            'adjustment_date' => ['from' => 'adjustment_date_from', 'to' => 'adjustment_date_to'],
        ]);
    }
}
