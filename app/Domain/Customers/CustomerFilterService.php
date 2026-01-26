<?php

namespace App\Domain\Customers;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for customer queries.
 *
 * Provides search, advanced filtering, and sorting functionality for customer listings.
 */
class CustomerFilterService
{
    use BaseFilterService;

    /**
     * Apply advanced filters for customers (branch, customer_type, status).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Customer>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Branch filter (by foreign key)
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Category filter
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}
