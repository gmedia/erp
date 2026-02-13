<?php

namespace App\Actions\AssetModels;

use App\Domain\AssetModels\AssetModelFilterService;
use App\Http\Requests\AssetModels\IndexAssetModelRequest;
use App\Models\AssetModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetModelsAction
{
    public function __construct(
        private AssetModelFilterService $filterService
    ) {}

    public function execute(IndexAssetModelRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = AssetModel::query()->with(['category']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['model_name', 'manufacturer']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'asset_category_id' => $request->get('asset_category_id'),
            ]);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'category') {
            $query
                ->leftJoin('asset_categories', 'asset_models.asset_category_id', '=', 'asset_categories.id')
                ->select('asset_models.*')
                ->orderBy('asset_categories.name', $sortDirection);
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                ['id', 'model_name', 'manufacturer', 'asset_category_id', 'created_at', 'updated_at']
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
