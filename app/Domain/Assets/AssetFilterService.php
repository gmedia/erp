<?php

namespace App\Domain\Assets;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\Asset> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['asset_category_id'])) {
            $query->where('asset_category_id', $filters['asset_category_id']);
        }
        if (!empty($filters['asset_model_id'])) {
            $query->where('asset_model_id', $filters['asset_model_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['asset_location_id'])) {
            $query->where('asset_location_id', $filters['asset_location_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (!in_array($sortBy, $allowedSorts)) {
            return;
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'category') {
            $query->join('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
                ->orderBy('asset_categories.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'branch') {
            $query->join('branches', 'assets.branch_id', '=', 'branches.id')
                ->orderBy('branches.name', $sortDirection)
                ->select('assets.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
