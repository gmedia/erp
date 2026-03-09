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
     * Apply search filters to query across multiple fields and optionally relationships.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @param  array<string, array<int, string>>  $relationSearchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields, array $relationSearchFields = []): void
    {
        $query->where(function ($q) use ($search, $searchFields, $relationSearchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }

            foreach ($relationSearchFields as $relation => $fields) {
                $q->orWhereHas($relation, function ($relationQuery) use ($search, $fields) {
                    $relationQuery->where(function ($rq) use ($search, $fields) {
                        foreach ($fields as $field) {
                            $rq->orWhere($field, 'like', "%{$search}%");
                        }
                    });
                });
            }
        });
    }

    /**
     * Apply sorting to query with validation against allowed columns.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     * @param  array<int, string>  $allowedSorts
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
