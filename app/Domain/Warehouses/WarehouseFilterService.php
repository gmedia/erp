<?php

namespace App\Domain\Warehouses;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for warehouse queries.
 *
 * Provides search and sorting functionality for warehouse listings.
 */
class WarehouseFilterService
{
    use BaseFilterService {
        applySearch as private applyBaseSearch;
    }

    /**
     * @param  Builder<\App\Models\Warehouse>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch($query, $search, $this->qualifySearchFields('warehouses', $searchFields));
    }

    /**
     * @param  Builder<\App\Models\Warehouse>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
        ]);
    }

    /**
     * @param  Builder<\App\Models\Warehouse>  $query
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
                'branch' => $this->relationSortConfig('branches', 'warehouses.branch_id', join: 'leftJoin'),
            ],
            'warehouses',
        );
    }
}
