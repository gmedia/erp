<?php

namespace App\Domain\Pipelines;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PipelineFilterService
{
    use BaseFilterService {
        applySearch as private applyBaseSearch;
    }

    /**
     * @param  Builder<\App\Models\Pipeline>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch($query, $search, $this->qualifySearchFields('pipelines', $searchFields));
    }

    /**
     * @param  Builder<\App\Models\Pipeline>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $this->applyExactFilters($query, $filters, [
                'entity_type' => 'entity_type',
            ]);
        }

        $this->applyResolvedBooleanFilter(
            $query,
            $filters,
            'is_active',
            static fn (mixed $value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * @param  Builder<\App\Models\Pipeline>  $query
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
                'created_by' => $this->relationSortConfig('users', 'pipelines.created_by', join: 'leftJoin', tableAlias: 'creator'),
            ],
            'pipelines',
        );
    }
}
