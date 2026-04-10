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
        $query = InventoryStocktake::query()->with(['warehouse', 'productCategory']);

        return $this->handleIndexRequestWithStatusExclusion($request, $query, $this->filterService, ['stocktake_number', 'notes'], [
            'warehouse_id',
            'product_category_id',
            'status',
            'stocktake_date_from',
            'stocktake_date_to',
        ], 'created_at', [
            'id',
            'stocktake_number',
            'warehouse_id',
            'stocktake_date',
            'status',
            'product_category_id',
            'created_at',
            'updated_at',
        ], 'cancelled');
    }
}
