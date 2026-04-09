<?php

namespace App\Domain\AssetModels;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetModelFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\AssetModel>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'asset_category_id' => 'asset_category_id',
        ]);
    }
}
