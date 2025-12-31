<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Builder;

class EmployeeFilterService
{
    /**
     * Apply search filters to employee query
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
     * Apply advanced filters for employees (department, position, salary, hire date)
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Department filter (exact match)
        if (! empty($filters['department'])) {
            $query->where('department', 'like', $filters['department']);
        }

        // Position filter (exact match)
        if (! empty($filters['position'])) {
            $query->where('position', 'like', $filters['position']);
        }

        // Salary range filtering
        if (! empty($filters['salary_min'])) {
            $query->where('salary', '>=', $filters['salary_min']);
        }

        if (! empty($filters['salary_max'])) {
            $query->where('salary', '<=', $filters['salary_max']);
        }

        // Hire date range filtering
        if (! empty($filters['hire_date_from'])) {
            $query->whereDate('hire_date', '>=', $filters['hire_date_from']);
        }

        if (! empty($filters['hire_date_to'])) {
            $query->whereDate('hire_date', '<=', $filters['hire_date_to']);
        }
    }

    /**
     * Apply sorting to employee query
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
