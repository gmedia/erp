<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class BudgetExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        protected array $filters = [],
    ) {}

    public function query(): Builder
    {
        $query = Budget::query()->with(['fiscalYear', 'creator']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['name', 'description'],
            ['fiscal_year_id' => 'fiscal_year_id', 'budget_type' => 'budget_type', 'status' => 'status'],
        );

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($budget): array
    {
        return $this->mapExportRow($budget, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (Budget $b): mixed => $b->id,
            'Name' => fn (Budget $b): mixed => $b->name,
            'Fiscal Year' => fn (Budget $b): mixed => $this->relatedAttribute($b, 'fiscalYear', 'name'),
            'Budget Type' => fn (Budget $b): mixed => ucfirst($b->budget_type),
            'Status' => fn (Budget $b): mixed => ucfirst($b->status),
            'Total Amount' => fn (Budget $b): mixed => (float) $b->total_amount,
            'Created By' => fn (Budget $b): mixed => $this->relatedAttribute($b, 'creator', 'name'),
            'Created At' => fn (Budget $b): mixed => $this->formatIso8601($b->created_at),
        ];
    }
}
