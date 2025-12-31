<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Builder;

class DepartmentFilterService
{
    /**
     * Apply search filters to department query
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
     * Apply sorting to department query
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
