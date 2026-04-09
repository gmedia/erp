<?php

namespace App\Actions\StockAdjustments;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\StockAdjustments\StockAdjustmentFilterService;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexStockAdjustmentsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private StockAdjustmentFilterService $filterService
    ) {}

    public function execute(IndexStockAdjustmentRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = StockAdjustment::query()->with(['warehouse', 'inventoryStocktake']);

        $this->applyRequestSearch($request, $query, $this->filterService, ['adjustment_number', 'notes']);
        $this->excludeStatusWhenFilterMissing($request, $query, 'cancelled');

        $this->applyRequestFilters($request, $query, $this->filterService, [
            'warehouse_id',
            'status',
            'adjustment_type',
            'inventory_stocktake_id',
            'adjustment_date_from',
            'adjustment_date_to',
        ]);

        $this->applyIndexSorting($request, $query, $this->filterService, 'created_at', [
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
        ]);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
