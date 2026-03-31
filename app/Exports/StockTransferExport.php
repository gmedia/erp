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

        $this->applySearchFilter($query, $this->filters, ['transfer_number', 'notes']);
        $this->applyExactFilters($query, $this->filters, [
            'from_warehouse_id' => 'from_warehouse_id',
            'to_warehouse_id' => 'to_warehouse_id',
            'status' => 'status',
        ]);
        $this->applyDateRangeFilters($query, $this->filters, [
            'transfer_date' => ['from' => 'transfer_date_from', 'to' => 'transfer_date_to'],
        ]);
        $this->applySorting($query, $this->filters, [
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
        return [
            'ID',
            'Transfer Number',
            'From Warehouse',
            'To Warehouse',
            'Transfer Date',
            'Expected Arrival Date',
            'Status',
            'Created At',
        ];
    }

    public function map($stockTransfer): array
    {
        return [
            $stockTransfer->id,
            $stockTransfer->transfer_number,
            $stockTransfer->fromWarehouse?->name,
            $stockTransfer->toWarehouse?->name,
            $stockTransfer->transfer_date?->toDateString(),
            $stockTransfer->expected_arrival_date?->toDateString(),
            $stockTransfer->status,
            $stockTransfer->created_at?->toIso8601String(),
        ];
    }
}
