<?php

namespace App\Exports;

use App\Models\AssetStocktake;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetStocktakeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        // Search
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%");
            });
        }

        // Filters
        if (!empty($this->filters['branch'])) {
            $query->where('branch_id', $this->filters['branch']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['reference', 'branch_id', 'planned_at', 'performed_at', 'status', 'created_at'];
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Reference', 'Branch', 'Planned At', 'Performed At', 'Status', 'Created By', 'Created At'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->reference,
            $row->branch?->name,
            $row->planned_at?->format('Y-m-d H:i'),
            $row->performed_at?->format('Y-m-d H:i'),
            $row->status,
            $row->createdBy?->name,
            $row->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
