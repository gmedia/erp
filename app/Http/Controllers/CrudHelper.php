<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait to provide common CRUD helper methods for controllers
 */
trait CrudHelper
{
    /**
     * Apply search filters to a query builder
     */
    protected function applySearch(Builder $query, Request $request, array $searchFields): void
    {
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
    }

    /**
     * Apply sorting to a query builder
     */
    protected function applySorting(Builder $query, Request $request, array $allowedSorts): void
    {
        $request->validate([
            'sort_by' => ['sometimes', 'in:' . implode(',', $allowedSorts)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ]);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }

    /**
     * Apply advanced filters for complex entities (like employees)
     */
    protected function applyAdvancedFilters(Builder $query, Request $request, bool $includeDepartmentPosition = true): void
    {
        // Department filter (exact match) - only when not searching
        if ($includeDepartmentPosition && $request->filled('department')) {
            $query->where('department', 'like', $request->get('department'));
        }

        // Position filter (exact match) - only when not searching
        if ($includeDepartmentPosition && $request->filled('position')) {
            $query->where('position', 'like', $request->get('position'));
        }

        // Salary range filtering - always applied
        if ($request->filled('salary_min')) {
            $query->where('salary', '>=', $request->get('salary_min'));
        }

        if ($request->filled('salary_max')) {
            $query->where('salary', '<=', $request->get('salary_max'));
        }

        // Hire date range filtering - always applied
        if ($request->filled('hire_date_from')) {
            $query->whereDate('hire_date', '>=', $request->get('hire_date_from'));
        }

        if ($request->filled('hire_date_to')) {
            $query->whereDate('hire_date', '<=', $request->get('hire_date_to'));
        }
    }
}
