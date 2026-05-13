<?php

namespace App\Domain\RecurringJournals;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class RecurringJournalFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'frequency' => 'frequency',
                'is_active' => 'is_active',
                'fiscal_year_id' => 'fiscal_year_id',
            ],
            [
                'next_run_date' => ['from' => 'next_run_from', 'to' => 'next_run_to'],
            ],
        );
    }
}
