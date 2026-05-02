<?php

namespace App\Domain\Products;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ProductFilterService
{
    use BaseFilterService {
        applySearch as private applyBaseSearch;
    }

    /**
     * @param  Builder<\App\Models\Product>  $query
     * @param  array<int, string>  $searchFields
     */
    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyBaseSearch($query, $search, $this->qualifySearchFields('products', $searchFields));
    }

    /**
     * @param  Builder<\App\Models\Product>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'product_category_id' => 'product_category_id',
            'unit_id' => 'unit_id',
            'branch_id' => 'branch_id',
            'type' => 'type',
            'status' => 'status',
            'billing_model' => 'billing_model',
        ]);
    }

    /**
     * @param  Builder<\App\Models\Product>  $query
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
                'category' => $this->relationSortConfig('product_categories', 'products.product_category_id', join: 'leftJoin'),
            ],
            'products',
        );
    }
}
