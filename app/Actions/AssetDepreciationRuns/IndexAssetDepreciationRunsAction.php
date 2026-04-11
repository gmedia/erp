<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetDepreciationRuns\AssetDepreciationRunFilterService;
use App\Http\Requests\AssetDepreciationRuns\IndexAssetDepreciationRunRequest;
use App\Models\AssetDepreciationRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetDepreciationRunsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetDepreciationRunFilterService $filterService
    ) {}

    public function execute(IndexAssetDepreciationRunRequest $request): LengthAwarePaginator
    {
        $query = AssetDepreciationRun::query()
            ->with(['fiscalYear', 'createdBy', 'postedBy'])
            ->withCount('lines');

        return $this->handleFilteredIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['fiscal_year_id', 'start_date', 'end_date', 'status'],
            'period_start',
            ['period_start', 'period_end', 'status', 'created_at'],
        );
    }
}
