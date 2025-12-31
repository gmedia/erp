<?php

namespace App\Actions;

use App\Domain\PositionFilterService;
use App\Http\Requests\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPositionsAction
{
    public function __construct(
        private PositionFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated positions with filters.
     *
     * @param IndexPositionRequest $request
     * @return LengthAwarePaginator
     */
    public function execute(IndexPositionRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

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

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get pagination parameters from request
     */
    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
