<?php

namespace App\Domain\Accounts;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for Account queries.
 */
class AccountFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters for accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Account>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        
        if (! empty($filters['coa_version_id'])) {
            $query->where('coa_version_id', $filters['coa_version_id']);
        }
    }
}
