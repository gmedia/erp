<?php

namespace App\Domain\Employees;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class EmployeeFilterService
{
    use BaseFilterService;

    private bool $employmentJoined = false;

    /**
     * Apply search across employee fields, qualifying bare column names
     * with "employees." prefix to avoid ambiguous-column errors after
     * the employment join is added by applyAdvancedFilters.
     *
     * Fully overrides the trait method instead of delegating, because
     * trait methods cannot be called statically.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @param  array<string, array<int, string>>  $relationSearchFields
     */
    public function applySearch(
        Builder $query,
        string $search,
        array $searchFields,
        array $relationSearchFields = []
    ): void {
        $qualifiedFields = $this->qualifySearchFields('employees', $searchFields);

        $query->where(function ($q) use ($search, $qualifiedFields, $relationSearchFields) {
            foreach ($qualifiedFields as $field) {
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

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->ensureEmploymentJoin($query);

        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'department_id' => 'employments.department_id',
                'position_id' => 'employments.position_id',
                'branch_id' => 'employments.branch_id',
                'employment_status' => 'employments.employment_status',
            ],
            [
                'employments.hire_date' => ['from' => 'hire_date_from', 'to' => 'hire_date_to'],
            ],
            [
                'employments.salary' => ['min' => 'salary_min', 'max' => 'salary_max'],
            ],
        );
    }

    private function ensureEmploymentJoin(Builder $query): void
    {
        if ($this->employmentJoined) {
            return;
        }

        $query->select('employees.*')
            ->leftJoin('employments', function ($join) {
                $join->on('employees.id', '=', 'employments.employee_id')
                    ->where('employments.is_current', true);
            });

        $this->employmentJoined = true;
    }
}
