<?php

namespace App\Actions\StockTransfers;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\StockTransfers\StockTransferFilterService;
use App\Http\Requests\StockTransfers\IndexStockTransferRequest;
use App\Models\StockTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexStockTransfersAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private StockTransferFilterService $filterService
    ) {}

    public function execute(IndexStockTransferRequest $request): LengthAwarePaginator
    {
        $query = StockTransfer::query()->with(['fromWarehouse', 'toWarehouse']);

        return $this->handleIndexRequestWithStatusExclusion($request, $query, $this->filterService, ['transfer_number', 'notes'], [
            'from_warehouse_id',
            'to_warehouse_id',
            'status',
            'transfer_date_from',
            'transfer_date_to',
            'expected_arrival_date_from',
            'expected_arrival_date_to',
        ], 'created_at', [
            'id',
            'transfer_number',
            'from_warehouse_id',
            'to_warehouse_id',
            'transfer_date',
            'expected_arrival_date',
            'status',
            'created_at',
            'updated_at',
        ], 'cancelled');
    }
}
