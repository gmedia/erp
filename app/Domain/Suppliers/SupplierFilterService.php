<?php

namespace App\Domain\Suppliers;

use App\Domain\Concerns\AppliesPartyExactFilters;
use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class SupplierFilterService
{
    use AppliesPartyExactFilters;
    use BaseFilterService;

    /**
     * Apply all filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Supplier>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        $this->applySearch($query, $filters['search'] ?? '', ['name', 'email', 'phone', 'address']);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        // Map frontend sort keys to backend columns
        $sortBy = match ($sortBy) {
            'branch' => 'branch_id',
            'category' => 'category_id',
            default => $sortBy
        };

        $this->applySorting(
            $query,
            $sortBy,
            $filters['sort_direction'] ?? 'desc',
            [
                'name',
                'email',
                'phone',
                'status',
                'branch_id',
                'category_id',
                'created_at',
            ],
        );
        $this->applyAdvancedFilters($query, $filters);
    }

    /**
     * Apply advanced filters for suppliers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Supplier>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyPartyExactFilters($query, $filters);
    }
}
