<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Employee::query();

        // Apply search filter
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply department filter
        if (!empty($this->filters['department'])) {
            $query->where('department', $this->filters['department']);
        }

        // Apply position filter
        if (!empty($this->filters['position'])) {
            $query->where('position', $this->filters['position']);
        }

        // Apply salary range filters
        if (!empty($this->filters['min_salary'])) {
            $query->where('salary', '>=', $this->filters['min_salary']);
        }

        if (!empty($this->filters['max_salary'])) {
            $query->where('salary', '<=', $this->filters['max_salary']);
        }

        // Apply hire date range filters
        if (!empty($this->filters['hire_date_from'])) {
            $query->where('hire_date', '>=', $this->filters['hire_date_from']);
        }

        if (!empty($this->filters['hire_date_to'])) {
            $query->where('hire_date', '<=', $this->filters['hire_date_to']);
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        
        // Validate sort_by to prevent SQL injection
        $allowedSortColumns = ['name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at'];
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
            $employee->department,
            $employee->position,
            $employee->salary,
            $employee->hire_date->format('Y-m-d'),
            $employee->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}