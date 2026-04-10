<?php

namespace App\Actions\StockMovements;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\StockMovements\StockMovementFilterService;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexStockMovementsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private StockMovementFilterService $filterService
    ) {}

    public function execute(IndexStockMovementRequest $request): LengthAwarePaginator|Collection
    {
        $query = StockMovement::query()->with([
            'product',
            'warehouse.branch',
            'createdBy',
        ]);

        return $this->handleIndexRequestWithOptionalExport(
            $request,
            $query,
            $this->filterService,
            ['reference_number', 'notes'],
            ['product_id', 'warehouse_id', 'movement_type', 'start_date', 'end_date'],
            'moved_at',
            [
                'moved_at',
                'reference_number',
                'quantity_in',
                'quantity_out',
                'balance_after',
                'unit_cost',
                'average_cost_after',
                'movement_type',
                'product_name',
                'warehouse_name',
                'created_by',
                'created_at',
            ],
        );
    }
}
