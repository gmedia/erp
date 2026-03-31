<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\ApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ApprovalFlowExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = ApprovalFlow::query()->with(['creator']);

        $this->applySearchFilter($query, $this->filters, ['name', 'code']);
        $this->applyPresentFilters($query, $this->filters, [
            'approvable_type' => 'approvable_type',
            'is_active' => 'is_active',
        ]);
        $this->applySorting($query, $this->filters, ['name', 'code', 'approvable_type', 'is_active', 'created_at']);

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
}
