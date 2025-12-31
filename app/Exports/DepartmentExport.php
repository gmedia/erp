<?php

namespace App\Exports;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartmentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @var array<string, mixed>
     */
    protected $filters;

    /**
     * @var Builder|null
     */
    protected $query;

    /**
     * Create a new export instance.
     *
     * @param  array<string, mixed>  $filters
     * @param  Builder|null  $query
     * @return void
     */
    public function __construct(array $filters = [], ?Builder $query = null)
    {
        $this->filters = $filters;
        $this->query = $query;
    }

    /**
     * Build the query for the export.
     */
    public function query(): Builder
    {
        if ($this->query) {
            return $this->query;
        }

        $query = Department::query();

        // Apply search filter if provided (used by frontend)
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply name filter if provided (fallback for direct API calls)
        if (! empty($this->filters['name'])) {
            $name = $this->filters['name'];
            $query->where('name', 'like', "%{$name}%");
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';

        // Whitelist sortable columns to prevent injection
        $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    /**
     * Define the headings for the Excel sheet.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map each Department model to a row.
     *
     * @param  Department  $department
     * @return array<int, mixed>
     */
    public function map($department): array
    {
        return [
            $department->id,
            $department->name,
            $department->created_at ? $department->created_at->format('Y-m-d H:i:s') : null,
            $department->updated_at ? $department->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Apply styles to the worksheet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Bold the header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
