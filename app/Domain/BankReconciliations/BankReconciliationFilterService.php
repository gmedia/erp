<?php

namespace App\Domain\BankReconciliations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class BankReconciliationFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            ['status' => 'status', 'account_id' => 'account_id', 'fiscal_year_id' => 'fiscal_year_id'],
            ['reconciliation_date' => ['from' => 'date_from', 'to' => 'date_to']],
        );
    }
}
