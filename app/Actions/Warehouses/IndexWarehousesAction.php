<?php

namespace App\Actions\Warehouses;

use App\Domain\Warehouses\WarehouseFilterService;
use App\Http\Requests\Warehouses\IndexWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated warehouses with filtering and sorting.
 */
class IndexWarehousesAction
{
    public function __construct(
        private WarehouseFilterService $filterService
    ) {}

    public function execute(IndexWarehouseRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Warehouse::query()->with(['branch']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['code', 'name']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'branch_id' => $request->get('branch_id'),
            ]);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'branch') {
            $query
                ->leftJoin('branches', 'warehouses.branch_id', '=', 'branches.id')
                ->select('warehouses.*')
                ->orderBy('branches.name', $sortDirection);
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                ['id', 'code', 'name', 'branch_id', 'created_at', 'updated_at']
            );
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
