<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Domain\AssetDepreciationRuns\AssetDepreciationRunFilterService;
use App\Http\Requests\AssetDepreciationRuns\IndexAssetDepreciationRunRequest;
use App\Models\AssetDepreciationRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetDepreciationRunsAction
{
    public function __construct(
        private AssetDepreciationRunFilterService $filterService
    ) {}

    public function execute(IndexAssetDepreciationRunRequest $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = AssetDepreciationRun::query()
            ->with(['fiscalYear', 'createdBy', 'postedBy'])
            ->withCount('lines');

        $this->filterService->applyAdvancedFilters($query, [
            'fiscal_year_id' => $request->get('fiscal_year_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'period_start'),
            $request->get('sort_direction', 'desc'),
            ['period_start', 'period_end', 'status', 'created_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
