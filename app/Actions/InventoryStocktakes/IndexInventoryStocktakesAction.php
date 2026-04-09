<?php

namespace App\Actions\InventoryStocktakes;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\InventoryStocktakes\InventoryStocktakeFilterService;
use App\Http\Requests\InventoryStocktakes\IndexInventoryStocktakeRequest;
use App\Models\InventoryStocktake;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexInventoryStocktakesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private InventoryStocktakeFilterService $filterService
    ) {}

    public function execute(IndexInventoryStocktakeRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = InventoryStocktake::query()->with(['warehouse', 'productCategory']);

        $this->applyRequestSearch($request, $query, $this->filterService, ['stocktake_number', 'notes']);
        $this->excludeStatusWhenFilterMissing($request, $query, 'cancelled');

        $this->applyRequestFilters($request, $query, $this->filterService, [
            'warehouse_id',
            'product_category_id',
            'status',
            'stocktake_date_from',
            'stocktake_date_to',
        ]);

        $this->applyIndexSorting($request, $query, $this->filterService, 'created_at', [
            'id',
            'stocktake_number',
            'warehouse_id',
            'stocktake_date',
            'status',
            'product_category_id',
            'created_at',
            'updated_at',
        ]);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
