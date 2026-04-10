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
        $query = Warehouse::query()->with(['branch']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['code', 'name'],
            ['branch_id'],
            [],
            'created_at',
            ['id', 'code', 'name', 'branch_id', 'branch', 'created_at', 'updated_at'],
        );
    }
}
