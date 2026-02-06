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
}
