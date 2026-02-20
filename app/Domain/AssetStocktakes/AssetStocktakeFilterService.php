<?php

namespace App\Domain\AssetStocktakes;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetStocktakeFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['planned_at_from'])) {
            $query->whereDate('planned_at', '>=', $filters['planned_at_from']);
        }

        if (!empty($filters['planned_at_to'])) {
            $query->whereDate('planned_at', '<=', $filters['planned_at_to']);
        }
    }
}
