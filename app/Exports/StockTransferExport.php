<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockTransferExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

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

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($stockTransfer): array
    {
        return $this->mapExportRow($stockTransfer, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->id,
            'Transfer Number' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->transfer_number,
            'From Warehouse' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->fromWarehouse?->name,
            'To Warehouse' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->toWarehouse?->name,
            'Transfer Date' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->transfer_date?->toDateString(),
            'Expected Arrival Date' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->expected_arrival_date?->toDateString(),
            'Status' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->status,
            'Created At' => static fn (StockTransfer $stockTransfer): mixed => $stockTransfer->created_at?->toIso8601String(),
        ];
    }
}
