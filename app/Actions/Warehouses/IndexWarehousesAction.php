<?php

namespace App\Actions\Warehouses;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Warehouses\WarehouseFilterService;
use App\Http\Requests\Warehouses\IndexWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated warehouses with filtering and sorting.
 */
class IndexWarehousesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private WarehouseFilterService $filterService
    ) {}

    public function execute(IndexWarehouseRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Warehouse::query()->with(['branch']);

        $this->applySearchOrPrimaryFilters($request, $query, $this->filterService, ['code', 'name'], ['branch_id']);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $this->normalizeSortDirection($request->get('sort_direction', 'desc'));

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

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
