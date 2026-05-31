<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\PeriodClosing;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class PeriodClosingExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        protected array $filters = [],
    ) {}

    public function query(): Builder
    {
        $query = PeriodClosing::query()->with(['fiscalYear', 'closedBy', 'reopenedBy']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            [],
            [
                'status' => 'status',
                'closing_type' => 'closing_type',
                'fiscal_year_id' => 'fiscal_year_id',
                'period_year' => 'period_year',
                'period_month' => 'period_month',
            ],
        );

        return $query->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($periodClosing): array
    {
        return $this->mapExportRow($periodClosing, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (PeriodClosing $item): mixed => $item->id,
            'Fiscal Year' => fn (PeriodClosing $item): mixed => $this->relatedAttribute($item, 'fiscalYear', 'name'),
            'Period Month' => fn (PeriodClosing $item): mixed => $item->period_month,
            'Period Year' => fn (PeriodClosing $item): mixed => $item->period_year,
            'Closing Type' => fn (PeriodClosing $item): mixed => $item->closing_type,
            'Status' => fn (PeriodClosing $item): mixed => $item->status,
            'Net Income' => fn (PeriodClosing $item): mixed => (float) $item->net_income,
            'Closed By' => fn (PeriodClosing $item): mixed => $this->relatedAttribute($item, 'closedBy', 'name'),
            'Closed At' => fn (PeriodClosing $item): mixed => $this->formatIso8601($item->closed_at),
        ];
    }
}
