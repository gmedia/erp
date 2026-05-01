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

    protected array $filters;

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
        return $this->exportHeadings($this->columns());
    }

    public function map($flow): array
    {
        return $this->mapExportRow($flow, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (ApprovalFlow $f): mixed => $f->id,
            'Code' => fn (ApprovalFlow $f): mixed => $f->code,
            'Name' => fn (ApprovalFlow $f): mixed => $f->name,
            'Approvable Type' => fn (ApprovalFlow $f): mixed => $f->approvable_type,
            'Is Active' => fn (ApprovalFlow $f): mixed => $f->is_active ? 'Yes' : 'No',
            'Created By' => fn (ApprovalFlow $f): mixed => $this->relatedAttribute($f, 'creator', 'name'),
            'Created At' => fn (ApprovalFlow $f): mixed => $this->formatDateValue($f->created_at, 'Y-m-d'),
        ];
    }
}
