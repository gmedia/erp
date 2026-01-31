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
     * Apply status filter.
     */
    protected function filterStatus($query, $value)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }
}
