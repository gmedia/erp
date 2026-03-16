<?php

namespace App\Actions\StockAdjustments;

use App\Domain\StockAdjustments\StockAdjustmentFilterService;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexStockAdjustmentsAction
{
    public function __construct(
        private StockAdjustmentFilterService $filterService
    ) {}

    public function execute(IndexStockAdjustmentRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = StockAdjustment::query()->with(['warehouse', 'inventoryStocktake']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['adjustment_number', 'notes']);
        }

        if (! $request->filled('status')) {
            $query->where('status', '!=', 'cancelled');
        }

        $this->filterService->applyAdvancedFilters($query, [
            'warehouse_id' => $request->get('warehouse_id'),
            'status' => $request->get('status'),
            'adjustment_type' => $request->get('adjustment_type'),
            'inventory_stocktake_id' => $request->get('inventory_stocktake_id'),
            'adjustment_date_from' => $request->get('adjustment_date_from'),
            'adjustment_date_to' => $request->get('adjustment_date_to'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc'
                ? 'asc'
                : 'desc',
            [
                'id',
                'adjustment_number',
                'warehouse_id',
                'adjustment_date',
                'adjustment_type',
                'status',
                'inventory_stocktake_id',
                'journal_entry_id',
                'approved_by',
                'approved_at',
                'created_at',
                'updated_at',
            ],
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
