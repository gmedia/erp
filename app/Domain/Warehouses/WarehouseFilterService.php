<?php

namespace App\Domain\Warehouses;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter service for warehouse queries.
 *
 * Provides search and sorting functionality for warehouse listings.
 */
class WarehouseFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\Warehouse>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
        ]);
    }
}
