<?php

namespace App\Domain\FiscalYears;

use App\Domain\Concerns\BaseFilterService;

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
    public function applyAdvancedFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * Apply status filter.
     */
    protected function filterStatus($query, $value)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }
}
