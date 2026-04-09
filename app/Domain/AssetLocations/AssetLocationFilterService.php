<?php

namespace App\Domain\AssetLocations;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetLocationFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\AssetLocation>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'branch_id' => 'branch_id',
        ]);

        if (array_key_exists('parent_id', $filters)) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === '') {
                // Allow filtering for root locations (no parent)
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }
    }
}
