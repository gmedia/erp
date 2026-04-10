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

        $this->applyResolvedBooleanFilter(
            $query,
            $filters,
            'is_active',
            static function (mixed $value): ?bool {
                $normalizedValue = strtolower((string) $value);

                if (in_array($normalizedValue, ['true', '1'], true)) {
                    return true;
                }

                if (in_array($normalizedValue, ['false', '0'], true)) {
                    return false;
                }

                return null;
            },
        );
    }
}
