<?php

namespace App\Domain\FiscalYears;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for fiscal year queries.
 */
class FiscalYearFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters for fiscal years.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\FiscalYear>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'status' => 'status',
        ]);
    }
}
