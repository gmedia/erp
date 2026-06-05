<?php

namespace App\Domain\Budgets;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class BudgetFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'fiscal_year_id' => 'fiscal_year_id',
                'budget_type' => 'budget_type',
                'status' => 'status',
            ],
        );
    }
}
