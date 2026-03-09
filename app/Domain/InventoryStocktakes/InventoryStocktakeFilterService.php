<?php

namespace App\Domain\InventoryStocktakes;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class InventoryStocktakeFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\InventoryStocktake>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['product_category_id'])) {
            $query->where('product_category_id', $filters['product_category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['stocktake_date_from'])) {
            $query->whereDate('stocktake_date', '>=', $filters['stocktake_date_from']);
        }

        if (! empty($filters['stocktake_date_to'])) {
            $query->whereDate('stocktake_date', '<=', $filters['stocktake_date_to']);
        }
    }
}
