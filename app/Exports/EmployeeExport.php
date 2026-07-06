<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeeExport extends BaseExport
{
    public function query(): Builder
    {
        $query = Employee::query()->with(['department', 'position', 'branch']);

        $this->applySearchFilter($query, $this->filters, ['employee_id', 'name', 'email', 'phone']);
        $this->applyExactFilters($query, $this->filters, [
            'department_id' => 'department_id',
            'position_id' => 'position_id',
            'branch_id' => 'branch_id',
            'employment_status' => 'employment_status',
        ]);

        // Apply salary range filters
        if (! empty($this->filters['min_salary'])) {
            $query->where('salary', '>=', $this->filters['min_salary']);
        }

        if (! empty($this->filters['max_salary'])) {
            $query->where('salary', '<=', $this->filters['max_salary']);
        }

        // Apply hire date range filters
        if (! empty($this->filters['hire_date_from'])) {
            $query->where('hire_date', '>=', $this->filters['hire_date_from']);
        }

        if (! empty($this->filters['hire_date_to'])) {
            $query->where('hire_date', '<=', $this->filters['hire_date_to']);
        }

        $this->applySorting($query, $this->filters, [
            'name', 'email', 'phone', 'employee_id',
            'department_id', 'position_id', 'branch_id',
            'salary', 'employment_status', 'hire_date',
            'created_at', 'updated_at',
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
            'Department' => fn (Employee $e): mixed => $this->relatedAttribute($e, 'department', 'name'),
            'Position' => fn (Employee $e): mixed => $this->relatedAttribute($e, 'position', 'name'),
            'Branch' => fn (Employee $e): mixed => $this->relatedAttribute($e, 'branch', 'name'),
            'Salary' => fn (Employee $e): mixed => $e->salary,
            'Status' => fn (Employee $e): mixed => $e->employment_status,
            'Hire Date' => fn (Employee $e): mixed => $this->formatDateValue($e->hire_date, 'Y-m-d'),
            'Created At' => fn (Employee $e): mixed => $this->formatIso8601($e->created_at),
        ];
    }
}
