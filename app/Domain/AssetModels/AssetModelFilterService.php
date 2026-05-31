<?php

namespace App\Domain\AssetModels;

use App\Domain\Concerns\BaseFilterService;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Builder;

class AssetModelFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<AssetModel>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'asset_category_id' => 'asset_category_id',
        ]);
    }

    /**
     * @param  Builder<AssetModel>  $query
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
                'category' => $this->relationSortConfig(
                    'asset_categories',
                    'asset_models.asset_category_id',
                    'name',
                    join: 'leftJoin',
                ),
            ],
            'asset_models'
        );
    }
}
