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

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (!in_array($sortBy, $allowedSorts)) {
            return;
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'branch') {
            $query->join('branches', 'asset_stocktakes.branch_id', '=', 'branches.id')
                ->orderBy('branches.name', $sortDirection)
                ->select('asset_stocktakes.*');
        } elseif ($sortBy === 'created_by') {
            $query->join('users', 'asset_stocktakes.created_by', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('asset_stocktakes.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
