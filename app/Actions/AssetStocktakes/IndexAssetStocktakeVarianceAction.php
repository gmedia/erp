<?php

namespace App\Actions\AssetStocktakes;

use App\Domain\AssetStocktakes\AssetStocktakeVarianceQueryService;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetStocktakeVarianceAction
{
    public function __construct(
        private readonly AssetStocktakeVarianceQueryService $queryService
    ) {}

    public function execute(IndexAssetStocktakeVarianceRequest $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = $this->queryService->buildBaseQuery();
        $this->queryService->applyFilters($query, [
            'asset_stocktake_id' => $request->get('asset_stocktake_id'),
            'branch_id' => $request->get('branch_id'),
            'result' => $request->get('result'),
            'search' => $request->get('search'),
        ]);

        $sortBy = $request->get('sort_by', 'checked_at');
        $sortDirection = (string) $request->get('sort_direction', 'desc');
        $this->queryService->applySorting($query, $sortBy, $sortDirection);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
