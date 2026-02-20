<?php

namespace App\Domain\Employees;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for employee queries.
 *
 * Provides search, advanced filtering, and sorting functionality for employee listings.
 */
class EmployeeFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters for employees (department, position, salary, hire date).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Employee>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Department filter (by foreign key)
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Position filter (by foreign key)
        if (! empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }

        // Branch filter (by foreign key)
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Employment status filter
        if (! empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
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
}
