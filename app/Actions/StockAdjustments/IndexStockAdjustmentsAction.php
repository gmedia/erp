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
        $query = StockAdjustment::query()->with(['warehouse', 'inventoryStocktake']);

        return $this->handleIndexRequestWithStatusExclusion($request, $query, $this->filterService, ['adjustment_number', 'notes'], [
            'warehouse_id',
            'status',
            'adjustment_type',
            'inventory_stocktake_id',
            'adjustment_date_from',
            'adjustment_date_to',
        ], 'created_at', [
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
        ], 'cancelled');
    }
}
