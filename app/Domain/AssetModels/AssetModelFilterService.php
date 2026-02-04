<?php

namespace App\Domain\AssetModels;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetModelFilterService
{
    use BaseFilterService;

    /**
     * @param Builder<\App\Models\AssetModel> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['asset_category_id'])) {
            $query->where('asset_category_id', $filters['asset_category_id']);
        }
    }
}
