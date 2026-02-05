<?php

namespace App\Domain\AssetLocations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetLocationFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\AssetLocation> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === '') {
                // Allow filtering for root locations (no parent)
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }
    }
}
