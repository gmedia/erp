<?php

namespace App\Actions\AssetLocations;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetLocations\AssetLocationFilterService;
use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;
use App\Models\AssetLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetLocationsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetLocationFilterService $filterService
    ) {}

    public function execute(IndexAssetLocationRequest $request): LengthAwarePaginator
    {
        $query = AssetLocation::query()->with(['branch', 'parent']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['code', 'name'],
            ['branch_id', 'parent_id'],
            [],
            'created_at',
            ['id', 'code', 'name', 'branch_id', 'parent_id', 'branch', 'parent', 'created_at', 'updated_at'],
        );
    }
}
