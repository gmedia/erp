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
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'department_id' => 'department_id',
                'position_id' => 'position_id',
                'branch_id' => 'branch_id',
                'employment_status' => 'employment_status',
            ],
            [
                'hire_date' => ['from' => 'hire_date_from', 'to' => 'hire_date_to'],
            ],
            [
                'salary' => ['min' => 'salary_min', 'max' => 'salary_max'],
            ],
        );
    }
}
