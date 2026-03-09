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
        if (! empty($filters['delegator_user_id'])) {
            $query->where('delegator_user_id', $filters['delegator_user_id']);
        }

        if (! empty($filters['delegate_user_id'])) {
            $query->where('delegate_user_id', $filters['delegate_user_id']);
        }

        if (in_array(strtolower((string) ($filters['is_active'] ?? '')), ['true', '1'], true)) {
            $query->where('is_active', true);
        } elseif (in_array(strtolower((string) ($filters['is_active'] ?? '')), ['false', '0'], true)) {
            $query->where('is_active', false);
        }

        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }

        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }
    }
}
