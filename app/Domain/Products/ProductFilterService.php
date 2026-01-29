<?php

namespace App\Domain\Products;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class ProductFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\Product> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        // Foreign Key Filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Enum Filters
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['billing_model'])) {
            $query->where('billing_model', $filters['billing_model']);
        }

        // Flag Filters
        if (isset($filters['is_manufactured'])) {
            $query->where('is_manufactured', $filters['is_manufactured']);
        }

        if (isset($filters['is_purchasable'])) {
            $query->where('is_purchasable', $filters['is_purchasable']);
        }

        if (isset($filters['is_sellable'])) {
            $query->where('is_sellable', $filters['is_sellable']);
        }

        if (isset($filters['is_taxable'])) {
            $query->where('is_taxable', $filters['is_taxable']);
        }

        if (isset($filters['is_recurring'])) {
            $query->where('is_recurring', $filters['is_recurring']);
        }
    }
}
