<?php

namespace App\Domain\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Base filter service trait providing common search and sorting functionality.
 *
 * This trait provides reusable query filtering methods for Eloquent models.
 * Use in domain filter services to reduce code duplication.
 */
trait BaseFilterService
{
    /**
     * Apply search filters to query across multiple fields.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  string  $search
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $query->where(function ($q) use ($search, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply sorting to query with validation against allowed columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  string  $sortBy
     * @param  string  $sortDirection
     * @param  array<int, string>  $allowedSorts
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
