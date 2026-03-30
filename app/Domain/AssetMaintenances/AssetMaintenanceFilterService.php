<?php

namespace App\Domain\AssetMaintenances;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetMaintenanceFilterService
{
    use BaseFilterService;

    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $this->applySearchWithRelationAliases($query, $search, $searchFields, [
            'asset_name' => ['relation' => 'asset', 'column' => 'name'],
            'asset_code' => ['relation' => 'asset', 'column' => 'asset_code'],
        ]);
    }

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'asset_id' => 'asset_id',
            'maintenance_type' => 'maintenance_type',
            'status' => 'status',
            'supplier_id' => 'supplier_id',
            'created_by' => 'created_by',
        ]);

        $this->applyDateRanges($query, $filters, [
            'scheduled_at' => ['from' => 'scheduled_from', 'to' => 'scheduled_to'],
            'performed_at' => ['from' => 'performed_from', 'to' => 'performed_to'],
        ]);

        $this->applyNumericRanges($query, $filters, [
            'cost' => ['min' => 'cost_min', 'max' => 'cost_max'],
        ]);
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (! in_array($sortBy, $allowedSorts)) {
            return;
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'asset') {
            $query->join('assets', 'asset_maintenances.asset_id', '=', 'assets.id')
                ->orderBy('assets.asset_code', $sortDirection)
                ->select('asset_maintenances.*');

            return;
        }

        if ($sortBy === 'supplier') {
            $query->leftJoin('suppliers', 'asset_maintenances.supplier_id', '=', 'suppliers.id')
                ->orderBy('suppliers.name', $sortDirection)
                ->select('asset_maintenances.*');

            return;
        }

        $query->orderBy($sortBy, $sortDirection);
    }
}
