<?php

namespace App\Domain\ApprovalDelegations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ApprovalDelegationFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters to the ApprovalDelegation query.
     *
     * @param  Builder<\App\Models\ApprovalDelegation>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'delegator_user_id' => 'delegator_user_id',
                'delegate_user_id' => 'delegate_user_id',
            ],
            [
                'start_date' => ['from' => 'start_date_from', 'to' => 'start_date_to'],
            ],
        );

        if (in_array(strtolower((string) ($filters['is_active'] ?? '')), ['true', '1'], true)) {
            $query->where('is_active', true);
        } elseif (in_array(strtolower((string) ($filters['is_active'] ?? '')), ['false', '0'], true)) {
            $query->where('is_active', false);
        }
    }
}
