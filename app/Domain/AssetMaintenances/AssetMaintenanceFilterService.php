<?php

namespace App\Domain\AssetMaintenances;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetMaintenanceFilterService
{
    use BaseFilterService;

    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applyAssetAliasSearch($query, $search, $searchFields);
    }

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyConfiguredFilters(
            $query,
            $filters,
            [
                'asset_id' => 'asset_id',
                'maintenance_type' => 'maintenance_type',
                'status' => 'status',
                'supplier_id' => 'supplier_id',
                'created_by' => 'created_by',
            ],
            [
                'scheduled_at' => ['from' => 'scheduled_from', 'to' => 'scheduled_to'],
                'performed_at' => ['from' => 'performed_from', 'to' => 'performed_to'],
            ],
            [
                'cost' => ['min' => 'cost_min', 'max' => 'cost_max'],
            ],
        );
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'asset' => $this->relationSortConfig('assets', 'asset_maintenances.asset_id', 'asset_code'),
                'supplier' => $this->relationSortConfig('suppliers', 'asset_maintenances.supplier_id', join: 'leftJoin'),
            ],
            'asset_maintenances'
        );
    }
}
