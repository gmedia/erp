<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Employee::query()->with(['department', 'position', 'branch']);

        // Apply search filter
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply department filter (by department_id)
        if (! empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        // Apply position filter (by position_id)
        if (! empty($this->filters['position_id'])) {
            $query->where('position_id', $this->filters['position_id']);
        }

        // Apply branch filter (by branch_id)
        if (! empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

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

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';

        // Validate sort_by to prevent SQL injection
        $allowedSortColumns = ['name', 'email', 'phone', 'department_id', 'position_id', 'branch_id', 'salary', 'hire_date', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Position',
            'Branch',
            'Salary',
            'Hire Date',
            'Created At',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->department?->name,
            $employee->position?->name,
            $employee->branch?->name,
            $employee->salary,
            $employee->hire_date->format('Y-m-d'),
            $employee->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
