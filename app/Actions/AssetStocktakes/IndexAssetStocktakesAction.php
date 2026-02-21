<?php

namespace App\Actions\AssetStocktakes;

use App\Domain\AssetStocktakes\AssetStocktakeFilterService;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeRequest;
use App\Models\AssetStocktake;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetStocktakesAction
{
    public function __construct(
        private AssetStocktakeFilterService $filterService
    ) {}

    public function execute(IndexAssetStocktakeRequest $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['reference']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'branch_id' => $request->get('branch_id'),
                'status' => $request->get('status'),
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'planned_at_from' => $request->get('planned_at_from'),
            'planned_at_to' => $request->get('planned_at_to'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'reference', 'branch', 'planned_at', 'performed_at', 'status', 'created_by', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
