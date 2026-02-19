<?php

namespace App\Domain\AssetMaintenances;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetMaintenanceFilterService
{
    use BaseFilterService;

    public function applySearch(Builder $query, string $search, array $searchFields): void
    {
        $query->where(function ($q) use ($search, $searchFields) {
            foreach ($searchFields as $field) {
                if ($field === 'asset_name') {
                    $q->orWhereHas('asset', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });

                    continue;
                }

                if ($field === 'asset_code') {
                    $q->orWhereHas('asset', function ($sq) use ($search) {
                        $sq->where('asset_code', 'like', "%{$search}%");
                    });

                    continue;
                }

                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (! empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['scheduled_from'])) {
            $query->whereDate('scheduled_at', '>=', $filters['scheduled_from']);
        }

        if (! empty($filters['scheduled_to'])) {
            $query->whereDate('scheduled_at', '<=', $filters['scheduled_to']);
        }

        if (! empty($filters['performed_from'])) {
            $query->whereDate('performed_at', '>=', $filters['performed_from']);
        }

        if (! empty($filters['performed_to'])) {
            $query->whereDate('performed_at', '<=', $filters['performed_to']);
        }

        if (! empty($filters['cost_min'])) {
            $query->where('cost', '>=', $filters['cost_min']);
        }

        if (! empty($filters['cost_max'])) {
            $query->where('cost', '<=', $filters['cost_max']);
        }
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
