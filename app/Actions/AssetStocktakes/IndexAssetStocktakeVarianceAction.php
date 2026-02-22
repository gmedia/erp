<?php

namespace App\Actions\AssetStocktakes;

use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;
use App\Models\AssetStocktakeItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexAssetStocktakeVarianceAction
{
    public function execute(IndexAssetStocktakeVarianceRequest $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = AssetStocktakeItem::query()
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

        if ($request->filled('asset_stocktake_id')) {
            $query->where('asset_stocktake_id', $request->get('asset_stocktake_id'));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('stocktake', function (Builder $q) use ($request) {
                $q->where('branch_id', $request->get('branch_id'));
            });
        }

        if ($request->filled('result')) {
            $query->where('result', $request->get('result'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('asset', function (Builder $q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'checked_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'result', 'checked_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif ($sortBy === 'stocktake_reference') {
            $query->orderBy(
                \App\Models\AssetStocktake::select('reference')
                    ->whereColumn('asset_stocktakes.id', 'asset_stocktake_items.asset_stocktake_id'),
                $sortDirection
            );
        } elseif ($sortBy === 'asset_code' || $sortBy === 'asset_name') {
            $query->orderBy(
                \App\Models\Asset::select($sortBy === 'asset_code' ? 'asset_code' : 'name')
                    ->whereColumn('assets.id', 'asset_stocktake_items.asset_id'),
                $sortDirection
            );
        } elseif ($sortBy === 'expected_branch' || $sortBy === 'found_branch') {
             $column = $sortBy === 'expected_branch' ? 'expected_branch_id' : 'found_branch_id';
             $query->orderBy(
                 \App\Models\Branch::select('name')
                     ->whereColumn('branches.id', "asset_stocktake_items.{$column}"),
                 $sortDirection
             );
        } elseif ($sortBy === 'expected_location' || $sortBy === 'found_location') {
             $column = $sortBy === 'expected_location' ? 'expected_location_id' : 'found_location_id';
             $query->orderBy(
                 \App\Models\AssetLocation::select('name')
                     ->whereColumn('asset_locations.id', "asset_stocktake_items.{$column}"),
                 $sortDirection
             );
        } else {
            $query->orderBy('checked_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
