<?php

namespace App\Domain\StockMovements;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class StockMovementFilterService
{
    use BaseFilterService {
        applySearch as applyBaseSearch;
    }

    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch(
            $query,
            $search,
            $searchFields,
            [
                'product' => ['name', 'code'],
                'warehouse' => ['name', 'code'],
                'createdBy' => ['name', 'email'],
            ],
        );
    }

    /**
     * @param  Builder<\App\Models\StockMovement>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'product_id' => 'product_id',
                'warehouse_id' => 'warehouse_id',
                'movement_type' => 'movement_type',
            ],
            [
                'moved_at' => ['from' => 'start_date', 'to' => 'end_date'],
            ],
        );
    }

    /**
     * @param  Builder<\App\Models\StockMovement>  $query
     * @param  array<int, string>  $allowedSorts
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'product_name' => $this->relationSortConfig('products', 'stock_movements.product_id', 'name', join: 'leftJoin'),
                'warehouse_name' => $this->relationSortConfig('warehouses', 'stock_movements.warehouse_id', 'name', join: 'leftJoin'),
                'created_by' => $this->relationSortConfig('users', 'stock_movements.created_by', 'name', join: 'leftJoin'),
            ],
            'stock_movements'
        );
    }
}
