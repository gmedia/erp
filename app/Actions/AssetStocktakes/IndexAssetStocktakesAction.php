<?php

namespace App\Actions\AssetStocktakes;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetStocktakes\AssetStocktakeFilterService;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeRequest;
use App\Models\AssetStocktake;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetStocktakesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetStocktakeFilterService $filterService
    ) {}

    public function execute(IndexAssetStocktakeRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        $this->applySearchOrPrimaryFilters(
            $request,
            $query,
            $this->filterService,
            ['reference'],
            ['branch_id', 'status'],
        );

        $this->applyRequestFilters(
            $request,
            $query,
            $this->filterService,
            ['planned_at_from', 'planned_at_to'],
        );

        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
            'created_at',
            [
                'id',
                'ulid',
                'reference',
                'branch',
                'planned_at',
                'performed_at',
                'status',
                'created_by',
                'created_at',
                'updated_at',
            ],
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
