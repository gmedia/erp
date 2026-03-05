<?php

namespace App\Exports;

use App\Models\StockTransfer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTransferExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = StockTransfer::query()->with(['fromWarehouse', 'toWarehouse']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $this->filters['from_warehouse_id']);
        }

        if (!empty($this->filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $this->filters['to_warehouse_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['transfer_date_from'])) {
            $query->whereDate('transfer_date', '>=', $this->filters['transfer_date_from']);
        }

        if (!empty($this->filters['transfer_date_to'])) {
            $query->whereDate('transfer_date', '<=', $this->filters['transfer_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'transfer_date', 'expected_arrival_date', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
