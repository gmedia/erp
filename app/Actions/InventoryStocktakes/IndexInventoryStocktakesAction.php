<?php

namespace App\Actions\InventoryStocktakes;

use App\Domain\InventoryStocktakes\InventoryStocktakeFilterService;
use App\Http\Requests\InventoryStocktakes\IndexInventoryStocktakeRequest;
use App\Models\InventoryStocktake;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexInventoryStocktakesAction
{
    public function __construct(
        private InventoryStocktakeFilterService $filterService
    ) {}

    public function execute(IndexInventoryStocktakeRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = InventoryStocktake::query()->with(['warehouse', 'productCategory']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['stocktake_number', 'notes']);
        }

        if (! $request->filled('status')) {
            $query->where('status', '!=', 'cancelled');
        }

        $this->filterService->applyAdvancedFilters($query, [
            'warehouse_id' => $request->get('warehouse_id'),
            'product_category_id' => $request->get('product_category_id'),
            'status' => $request->get('status'),
            'stocktake_date_from' => $request->get('stocktake_date_from'),
            'stocktake_date_to' => $request->get('stocktake_date_to'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'stocktake_number', 'warehouse_id', 'stocktake_date', 'status', 'product_category_id', 'created_at', 'updated_at']
        );

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
