<?php

namespace App\Actions\AssetLocations;

use App\Domain\AssetLocations\AssetLocationFilterService;
use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;
use App\Models\AssetLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetLocationsAction
{
    public function __construct(
        private AssetLocationFilterService $filterService
    ) {}

    public function execute(IndexAssetLocationRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = AssetLocation::query()->with(['branch', 'parent']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['code', 'name']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'branch_id' => $request->get('branch_id'),
                'parent_id' => $request->get('parent_id'),
            ]);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'branch') {
            $query
                ->leftJoin('branches', 'asset_locations.branch_id', '=', 'branches.id')
                ->select('asset_locations.*')
                ->orderBy('branches.name', $sortDirection);
        } elseif ($sortBy === 'parent') {
            $query
                ->leftJoin('asset_locations as parents', 'asset_locations.parent_id', '=', 'parents.id')
                ->select('asset_locations.*')
                ->orderBy('parents.name', $sortDirection);
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                ['id', 'code', 'name', 'branch_id', 'parent_id', 'created_at', 'updated_at']
            );
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
