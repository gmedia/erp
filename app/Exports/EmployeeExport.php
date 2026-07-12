<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeeExport extends BaseExport
{
    public function query(): Builder
    {
        $query = Employee::query()
            ->join('employments', function ($join) {
                $join->on('employees.id', '=', 'employments.employee_id')
                    ->where('employments.is_current', '=', true);
            })
            ->with(['currentEmployment.department', 'currentEmployment.position', 'currentEmployment.branch'])
            ->select('employees.*');

        $this->applySearchFilter($query, $this->filters, ['employees.employee_id', 'employees.name', 'employees.email', 'employees.phone']);
        $this->applyExactFilters($query, $this->filters, [
            'department_id' => 'employments.department_id',
            'position_id' => 'employments.position_id',
            'branch_id' => 'employments.branch_id',
            'employment_status' => 'employments.employment_status',
        ]);

        // Apply salary range filters
        if (! empty($this->filters['salary_min'])) {
            $query->where('employments.salary', '>=', $this->filters['salary_min']);
        }

        if (! empty($this->filters['salary_max'])) {
            $query->where('employments.salary', '<=', $this->filters['salary_max']);
        }

        // Apply hire date range filters
        if (! empty($this->filters['hire_date_from'])) {
            $query->where('employments.hire_date', '>=', $this->filters['hire_date_from']);
        }

        if (! empty($this->filters['hire_date_to'])) {
            $query->where('employments.hire_date', '<=', $this->filters['hire_date_to']);
        }

        $this->applySorting($query, $this->filters, [
            'employees.name', 'employees.email', 'employees.phone', 'employees.employee_id',
            'employments.department_id', 'employments.position_id', 'employments.branch_id',
            'employments.salary', 'employments.employment_status', 'employments.hire_date',
            'employees.created_at', 'employees.updated_at',
        ]);

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Employee $e): mixed => $e->id,
            'NIK' => fn (Employee $e): mixed => $e->employee_id,
            'Name' => fn (Employee $e): mixed => $e->name,
            'Email' => fn (Employee $e): mixed => $e->email,
            'Phone' => fn (Employee $e): mixed => $e->phone,
            'Department' => fn (Employee $e): mixed => $this->relatedAttribute($e->currentEmployment, 'department', 'name'),
            'Position' => fn (Employee $e): mixed => $this->relatedAttribute($e->currentEmployment, 'position', 'name'),
            'Branch' => fn (Employee $e): mixed => $this->relatedAttribute($e->currentEmployment, 'branch', 'name'),
            'Salary' => fn (Employee $e): mixed => $e->currentEmployment?->salary,
            'Status' => fn (Employee $e): mixed => $e->currentEmployment?->employment_status,
            'Hire Date' => fn (Employee $e): mixed => $this->formatDateValue($e->currentEmployment?->hire_date, 'Y-m-d'),
            'Created At' => fn (Employee $e): mixed => $this->formatIso8601($e->created_at),
        ];
    }
}
