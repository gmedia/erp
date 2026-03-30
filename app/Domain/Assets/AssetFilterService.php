<?php

namespace App\Domain\Assets;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class AssetFilterService
{
    use BaseFilterService;

    /**
     * @param  Builder<\App\Models\Asset>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilters($query, $filters, [
            'asset_category_id' => 'asset_category_id',
            'asset_model_id' => 'asset_model_id',
            'branch_id' => 'branch_id',
            'asset_location_id' => 'asset_location_id',
            'department_id' => 'department_id',
            'employee_id' => 'employee_id',
            'supplier_id' => 'supplier_id',
            'status' => 'status',
            'condition' => 'condition',
        ]);
    }

    public function applySorting(Builder $query, string $sortBy, string $sortDirection, array $allowedSorts): void
    {
        if (! in_array($sortBy, $allowedSorts)) {
            return;
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'category') {
            $query->join('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
                ->orderBy('asset_categories.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'branch') {
            $query->join('branches', 'assets.branch_id', '=', 'branches.id')
                ->orderBy('branches.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'location') {
            $query->leftJoin('asset_locations', 'assets.asset_location_id', '=', 'asset_locations.id')
                ->orderBy('asset_locations.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'department') {
            $query->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
                ->orderBy('departments.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'employee') {
            $query->leftJoin('employees', 'assets.employee_id', '=', 'employees.id')
                ->orderBy('employees.name', $sortDirection)
                ->select('assets.*');
        } elseif ($sortBy === 'supplier') {
            $query->leftJoin('suppliers', 'assets.supplier_id', '=', 'suppliers.id')
                ->orderBy('suppliers.name', $sortDirection)
                ->select('assets.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }
}
