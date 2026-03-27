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
        $this->applyExactFilters($query, $filters, [
            'warehouse_id' => 'warehouse_id',
            'product_category_id' => 'product_category_id',
            'status' => 'status',
        ]);

        $this->applyDateRanges($query, $filters, [
            'stocktake_date' => ['from' => 'stocktake_date_from', 'to' => 'stocktake_date_to'],
        ]);
    }
}
