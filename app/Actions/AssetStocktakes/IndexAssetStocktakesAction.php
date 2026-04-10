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
        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['reference'],
            ['branch_id', 'status'],
            ['planned_at_from', 'planned_at_to'],
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
    }
}
