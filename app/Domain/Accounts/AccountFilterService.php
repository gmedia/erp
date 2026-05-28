<?php

namespace App\Domain\Accounts;

use App\Domain\Concerns\BaseFilterService;
use App\Models\Account;
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
     * @param  Builder<Account>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'type' => 'type',
            'coa_version_id' => 'coa_version_id',
        ]);

        $this->applyBooleanFilter($query, $filters, 'is_active', coerceValue: true, skipEmptyString: false);
    }
}
