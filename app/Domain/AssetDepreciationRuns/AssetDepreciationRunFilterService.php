<?php

namespace App\Domain\AssetDepreciationRuns;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetDepreciationRunFilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'fiscal_year_id' => 'fiscal_year_id',
            'status' => 'status',
        ]);

        if (! empty($filters['start_date'])) {
            $query->whereDate('period_start', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('period_end', '<=', $filters['end_date']);
        }
    }
}
