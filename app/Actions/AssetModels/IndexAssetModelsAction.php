<?php

namespace App\Actions\AssetModels;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetModels\AssetModelFilterService;
use App\Http\Requests\AssetModels\IndexAssetModelRequest;
use App\Models\AssetModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetModelsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetModelFilterService $filterService
    ) {}

    public function execute(IndexAssetModelRequest $request): LengthAwarePaginator
    {
        $query = AssetModel::query()->with(['category']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['model_name', 'manufacturer'],
            ['asset_category_id'],
            [],
            'created_at',
            ['id', 'model_name', 'manufacturer', 'asset_category_id', 'category', 'created_at', 'updated_at'],
        );
    }
}
