<?php

namespace App\Domain\AssetLocations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetLocationFilterService
{
    use BaseFilterService {
        applySearch as private applyBaseSearch;
    }

    /**
     * @param  Builder<\App\Models\AssetLocation>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch($query, $search, $this->qualifySearchFields('asset_locations', $searchFields));
    }

    /**
     * @param  Builder<\App\Models\AssetLocation>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
        ]);

        if (array_key_exists('parent_id', $filters)) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === '') {
                // Allow filtering for root locations (no parent)
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }
    }

    /**
     * @param  Builder<\App\Models\AssetLocation>  $query
     * @param  array<int, string>  $allowedSorts
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'branch' => $this->relationSortConfig('branches', 'asset_locations.branch_id', join: 'leftJoin'),
                'parent' => $this->relationSortConfig('asset_locations', 'asset_locations.parent_id', join: 'leftJoin', tableAlias: 'parents'),
            ],
            'asset_locations',
        );
    }
}
