<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder;

class StockTransferExport extends BaseExport
{
    public function __construct(protected readonly array $filters = []) {}

    public function query(): Builder
    {
        $query = StockTransfer::query()->with(['fromWarehouse', 'toWarehouse']);

        $this->applyConfiguredFilters($query, $this->filters, ['transfer_number', 'notes'], [
            'from_warehouse_id' => 'from_warehouse_id',
            'to_warehouse_id' => 'to_warehouse_id',
            'status' => 'status',
        ], [
            'transfer_date' => ['from' => 'transfer_date_from', 'to' => 'transfer_date_to'],
        ], [
            'transfer_number',
            'from_warehouse_id',
            'to_warehouse_id',
            'transfer_date',
            'expected_arrival_date',
            'status',
            'created_at',
        ]);

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (StockTransfer $item): mixed => $item->id,
            'Transfer Number' => fn (StockTransfer $item): mixed => $item->transfer_number,
            'From Warehouse' => fn (StockTransfer $item): mixed => $this->relatedAttribute(
                $item,
                'fromWarehouse',
                'name',
            ),
            'To Warehouse' => fn (StockTransfer $item): mixed => $this->relatedAttribute($item, 'toWarehouse', 'name'),
            'Transfer Date' => fn (StockTransfer $item): mixed => $this->formatDateValue($item->transfer_date, 'Y-m-d'),
            'Expected Arrival Date' => fn (StockTransfer $item): mixed => $this->formatDateValue(
                $item->expected_arrival_date,
                'Y-m-d',
            ),
            'Status' => fn (StockTransfer $item): mixed => $item->status,
            'Created At' => fn (StockTransfer $item): mixed => $this->formatIso8601($item->created_at),
        ];
    }
}
