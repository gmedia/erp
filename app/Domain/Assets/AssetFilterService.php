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
            $this->assetRelationSortMap(),
            'assets'
        );
    }

    /**
     * @return array<string, array{table: string, local_column: string, foreign_column: string, order_column: string, join?: 'join'|'leftJoin'}>
     */
    protected function assetRelationSortMap(): array
    {
        return [
            'category' => $this->relationSortConfig('asset_categories', 'assets.asset_category_id'),
            'branch' => $this->relationSortConfig('branches', 'assets.branch_id'),
            'location' => $this->relationSortConfig('asset_locations', 'assets.asset_location_id', join: 'leftJoin'),
            'department' => $this->relationSortConfig('departments', 'assets.department_id', join: 'leftJoin'),
            'employee' => $this->relationSortConfig('employees', 'assets.employee_id', join: 'leftJoin'),
            'supplier' => $this->relationSortConfig('suppliers', 'assets.supplier_id', join: 'leftJoin'),
        ];
    }
}
