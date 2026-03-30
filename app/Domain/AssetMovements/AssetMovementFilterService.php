<?php

namespace App\Domain\AssetMovements;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetMovementFilterService
{
    use BaseFilterService;

    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyAssetAliasSearch($query, $search, $searchFields);
    }

    /**
     * @param  Builder<\App\Models\AssetMovement>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'asset_id' => 'asset_id',
            'movement_type' => 'movement_type',
            'from_branch_id' => 'from_branch_id',
            'to_branch_id' => 'to_branch_id',
            'from_employee_id' => 'from_employee_id',
            'to_employee_id' => 'to_employee_id',
        ]);

        $this->applyDateRanges($query, $filters, [
            'moved_at' => ['from' => 'start_date', 'to' => 'end_date'],
        ]);
    }
}
