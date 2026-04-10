<?php

namespace App\Domain\Customers;

use App\Domain\Concerns\AppliesPartyExactFilters;
use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for customer queries.
 *
 * Provides search, advanced filtering, and sorting functionality for customer listings.
 */
class CustomerFilterService
{
    use AppliesPartyExactFilters;
    use BaseFilterService;

    /**
     * Apply advanced filters for customers (branch, customer_type, status).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Customer>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyPartyExactFilters($query, $filters);
    }
}
