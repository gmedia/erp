<?php

namespace App\Actions\Positions;

use App\Domain\Positions\PositionFilterService;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated positions with filtering and sorting.
 */
class IndexPositionsAction
{
    public function __construct(
        private PositionFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated positions with filters.
     *
     * @param  \App\Http\Requests\Positions\IndexPositionRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\Position>
     */
    public function execute(IndexPositionRequest $request): LengthAwarePaginator
    {
        $query = Position::query();

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
