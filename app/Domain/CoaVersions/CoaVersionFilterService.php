<?php

namespace App\Domain\CoaVersions;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for COA Version queries.
 */
class CoaVersionFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters for COA versions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\CoaVersion>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'status' => 'status',
            'fiscal_year_id' => 'fiscal_year_id',
        ]);
    }
}
