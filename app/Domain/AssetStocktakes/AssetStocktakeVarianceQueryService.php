<?php

namespace App\Domain\AssetStocktakes;

use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

class AssetStocktakeVarianceQueryService
{
    /**
     * @return Builder<AssetStocktakeItem>
     */
    public function buildBaseQuery(): Builder
    {
        return AssetStocktakeItem::query()
            ->with([
                'stocktake',
                'asset',
                'expectedBranch',
                'expectedLocation',
                'foundBranch',
                'foundLocation',
                'checkedBy',
            ])
            ->whereIn('result', ['missing', 'damaged', 'moved']);
    }

    /**
     * @param  Builder<AssetStocktakeItem>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['asset_stocktake_id'])) {
            $query->where('asset_stocktake_id', $filters['asset_stocktake_id']);
        }

        if (! empty($filters['branch_id'])) {
            $query->whereHas('stocktake', function (Builder $branchQuery) use ($filters): void {
                $branchQuery->where('branch_id', $filters['branch_id']);
            });
        }

        if (! empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->whereHas('asset', function (Builder $assetQuery) use ($search): void {
                $assetQuery->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
    }

    /**
     * @param  Builder<AssetStocktakeItem>  $query
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'result', 'checked_at'])) {
            $query->orderBy($sortBy, $direction);

            return;
        }

        if ($sortBy === 'stocktake_reference') {
            $query->orderBy(
                AssetStocktake::select('reference')
                    ->whereColumn('asset_stocktakes.id', 'asset_stocktake_items.asset_stocktake_id'),
                $direction
            );

            return;
        }

        if ($sortBy === 'asset_code' || $sortBy === 'asset_name') {
            $query->orderBy(
                Asset::select($sortBy === 'asset_code' ? 'asset_code' : 'name')
                    ->whereColumn('assets.id', 'asset_stocktake_items.asset_id'),
                $direction
            );

            return;
        }

        if ($sortBy === 'expected_branch' || $sortBy === 'found_branch') {
            $column = $sortBy === 'expected_branch' ? 'expected_branch_id' : 'found_branch_id';
            $query->orderBy(
                Branch::select('name')
                    ->whereColumn('branches.id', "asset_stocktake_items.{$column}"),
                $direction
            );

            return;
        }

        if ($sortBy === 'expected_location' || $sortBy === 'found_location') {
            $column = $sortBy === 'expected_location' ? 'expected_location_id' : 'found_location_id';
            $query->orderBy(
                AssetLocation::select('name')
                    ->whereColumn('asset_locations.id', "asset_stocktake_items.{$column}"),
                $direction
            );

            return;
        }

        $query->orderBy('checked_at', 'desc');
    }
}