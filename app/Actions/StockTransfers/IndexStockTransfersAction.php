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
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = StockTransfer::query()->with(['fromWarehouse', 'toWarehouse']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['transfer_number', 'notes']);
        }

        if (! $request->filled('status')) {
            $query->where('status', '!=', 'cancelled');
        }

        $this->filterService->applyAdvancedFilters($query, [
            'from_warehouse_id' => $request->get('from_warehouse_id'),
            'to_warehouse_id' => $request->get('to_warehouse_id'),
            'status' => $request->get('status'),
            'transfer_date_from' => $request->get('transfer_date_from'),
            'transfer_date_to' => $request->get('transfer_date_to'),
            'expected_arrival_date_from' => $request->get('expected_arrival_date_from'),
            'expected_arrival_date_to' => $request->get('expected_arrival_date_to'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            $this->normalizeSortDirection($request->get('sort_direction', 'desc')),
            [
                'id',
                'transfer_number',
                'from_warehouse_id',
                'to_warehouse_id',
                'transfer_date',
                'expected_arrival_date',
                'status',
                'created_at',
                'updated_at',
            ],
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
