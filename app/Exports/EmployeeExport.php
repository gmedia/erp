<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class EmployeeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

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
            'name',
            'email',
            'phone',
            'employee_id',
            'department_id',
            'position_id',
            'branch_id',
            'salary',
            'employment_status',
            'hire_date',
            'created_at',
            'updated_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'NIK',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Position',
            'Branch',
            'Salary',
            'Status',
            'Hire Date',
            'Created At',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->employee_id,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->department?->name,
            $employee->position?->name,
            $employee->branch?->name,
            $employee->salary,
            $employee->employment_status,
            $employee->hire_date->format('Y-m-d'),
            $employee->created_at?->toIso8601String(),
        ];
    }
}
