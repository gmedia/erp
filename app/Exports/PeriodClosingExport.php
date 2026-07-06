<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\PeriodClosing;
use Illuminate\Database\Eloquent\Builder;

class PeriodClosingExport extends BaseExport
{
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
