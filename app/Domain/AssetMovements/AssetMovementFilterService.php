<?php

namespace App\Domain\AssetMovements;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetMovementFilterService
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
                } elseif ($field === 'asset_code') {
                    $q->orWhereHas('asset', function ($sq) use ($search) {
                        $sq->where('asset_code', 'like', "%{$search}%");
                    });
                } else {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            }
        });
    }

    /**
     * @param Builder<\App\Models\AssetMovement> $query
     * @param array<string, mixed> $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }
        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }
        if (!empty($filters['from_branch_id'])) {
            $query->where('from_branch_id', $filters['from_branch_id']);
        }
        if (!empty($filters['to_branch_id'])) {
            $query->where('to_branch_id', $filters['to_branch_id']);
        }
        if (!empty($filters['from_employee_id'])) {
            $query->where('from_employee_id', $filters['from_employee_id']);
        }
        if (!empty($filters['to_employee_id'])) {
            $query->where('to_employee_id', $filters['to_employee_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('moved_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('moved_at', '<=', $filters['end_date']);
        }
    }
}
