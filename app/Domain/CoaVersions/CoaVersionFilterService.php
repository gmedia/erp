<?php

namespace App\Domain\CoaVersions;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for COA Version queries.
 */
class CoaVersionFilterService
{
    use BaseFilterService {
        applySearch as private applyBaseSearch;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\CoaVersion>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch($query, $search, $this->qualifySearchFields('coa_versions', $searchFields));
    }

    /**
     * Apply advanced filters for COA versions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\CoaVersion>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'status' => 'status',
            'fiscal_year_id' => 'fiscal_year_id',
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\CoaVersion>  $query
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
                'fiscal_year.name' => $this->relationSortConfig('fiscal_years', 'coa_versions.fiscal_year_id', join: 'leftJoin'),
                'fiscal_year_name' => $this->relationSortConfig('fiscal_years', 'coa_versions.fiscal_year_id', join: 'leftJoin'),
            ],
            'coa_versions',
        );
    }
}
