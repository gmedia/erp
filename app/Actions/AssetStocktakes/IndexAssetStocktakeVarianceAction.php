<?php

namespace App\Actions\AssetStocktakes;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetStocktakes\AssetStocktakeVarianceQueryService;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexAssetStocktakeVarianceAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private readonly AssetStocktakeVarianceQueryService $queryService
    ) {}

    public function execute(IndexAssetStocktakeVarianceRequest $request): LengthAwarePaginator|Collection
    {
        ['page' => $page] = $this->getPaginationParams($request);

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

        return $this->exportOrPaginate($request, $query, $page, false);
    }
}
