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
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['planned_at_from'])) {
            $query->whereDate('planned_at', '>=', $filters['planned_at_from']);
        }

        if (! empty($filters['planned_at_to'])) {
            $query->whereDate('planned_at', '<=', $filters['planned_at_to']);
        }
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (! in_array($sortBy, $allowedSorts)) {
            return;
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $applied = $this->applyMappedRelationSorting(
            $query,
            $sortBy,
            $sortDirection,
            [
                'branch' => [
                    'table' => 'branches',
                    'local_column' => 'asset_stocktakes.branch_id',
                    'foreign_column' => 'branches.id',
                    'order_column' => 'branches.name',
                ],
                'created_by' => [
                    'table' => 'users',
                    'local_column' => 'asset_stocktakes.created_by',
                    'foreign_column' => 'users.id',
                    'order_column' => 'users.name',
                ],
            ],
            'asset_stocktakes'
        );

        if (! $applied) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
