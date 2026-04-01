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
        $this->applySortingWithRelationFallback(
            $query,
            $sortBy,
            $sortDirection,
            $allowedSorts,
            [
                'category' => [
                    'table' => 'asset_categories',
                    'local_column' => 'assets.asset_category_id',
                    'foreign_column' => 'asset_categories.id',
                    'order_column' => 'asset_categories.name',
                ],
                'branch' => [
                    'table' => 'branches',
                    'local_column' => 'assets.branch_id',
                    'foreign_column' => 'branches.id',
                    'order_column' => 'branches.name',
                ],
                'location' => [
                    'table' => 'asset_locations',
                    'local_column' => 'assets.asset_location_id',
                    'foreign_column' => 'asset_locations.id',
                    'order_column' => 'asset_locations.name',
                    'join' => 'leftJoin',
                ],
                'department' => [
                    'table' => 'departments',
                    'local_column' => 'assets.department_id',
                    'foreign_column' => 'departments.id',
                    'order_column' => 'departments.name',
                    'join' => 'leftJoin',
                ],
                'employee' => [
                    'table' => 'employees',
                    'local_column' => 'assets.employee_id',
                    'foreign_column' => 'employees.id',
                    'order_column' => 'employees.name',
                    'join' => 'leftJoin',
                ],
                'supplier' => [
                    'table' => 'suppliers',
                    'local_column' => 'assets.supplier_id',
                    'foreign_column' => 'suppliers.id',
                    'order_column' => 'suppliers.name',
                    'join' => 'leftJoin',
                ],
            ],
            'assets'
        );
    }
}
