<?php

namespace App\Domain\AssetStocktakes;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetStocktakeFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters to the query.
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'branch_id' => 'branch_id',
                'status' => 'status',
            ],
            [
                'planned_at' => ['from' => 'planned_at_from', 'to' => 'planned_at_to'],
            ],
        );
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'branch' => $this->relationSortConfig('branches', 'asset_stocktakes.branch_id'),
                'created_by' => $this->relationSortConfig('users', 'asset_stocktakes.created_by'),
            ],
            'asset_stocktakes'
        );
    }
}
