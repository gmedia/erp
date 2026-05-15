<?php

namespace App\Domain\ReportConfigurations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ReportConfigurationFilterService
{
    use BaseFilterService;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'report_type' => 'report_type',
                'is_active' => 'is_active',
            ],
        );
    }
}
