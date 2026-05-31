<?php

namespace App\Domain\PeriodClosings;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class PeriodClosingFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters($query, $filters, [
            'status' => 'status',
            'closing_type' => 'closing_type',
            'fiscal_year_id' => 'fiscal_year_id',
            'period_year' => 'period_year',
            'period_month' => 'period_month',
        ]);
    }
}
