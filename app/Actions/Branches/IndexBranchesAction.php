<?php

namespace App\Actions\Branches;

use App\Domain\Branches\BranchFilterService;
use App\Http\Requests\Branches\IndexBranchRequest;
use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated branches with filtering and sorting.
 */
class IndexBranchesAction
{
    public function __construct(
        private BranchFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated branches with filters.
     *
     * @param  \App\Http\Requests\Branches\IndexBranchRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\Branch>
     */
    public function execute(IndexBranchRequest $request): LengthAwarePaginator
    {
        $query = Branch::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name']);
        }

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        return $query->paginate($request->get('per_page', 15));
    }
}
