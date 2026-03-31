<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\AssetStocktake;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetStocktakeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        $this->applySearchFilter($query, $this->filters, ['reference']);
        $this->applyExactFilters($query, $this->filters, [
            'branch' => 'branch_id',
            'status' => 'status',
        ]);
        $this->applySorting($query, $this->filters, ['reference', 'branch_id', 'planned_at', 'performed_at', 'status', 'created_at']);

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
}
