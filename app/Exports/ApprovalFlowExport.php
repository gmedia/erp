<?php

namespace App\Exports;

use App\Models\ApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApprovalFlowExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = ApprovalFlow::query()->with(['creator']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('approvable_type', $this->filters) && $this->filters['approvable_type'] !== '') {
            $query->where('approvable_type', $this->filters['approvable_type']);
        }
        
        if (array_key_exists('is_active', $this->filters) && $this->filters['is_active'] !== '') {
            $query->where('is_active', $this->filters['is_active']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['name', 'code', 'approvable_type', 'is_active', 'created_at'];
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Code', 'Name', 'Approvable Type', 'Is Active', 'Created By', 'Created At'];
    }

    public function map($flow): array
    {
        return [
            $flow->id,
            $flow->code,
            $flow->name,
            $flow->approvable_type,
            $flow->is_active ? 'Yes' : 'No',
            $flow->creator?->name,
            $flow->created_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
