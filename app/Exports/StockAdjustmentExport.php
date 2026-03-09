<?php

namespace App\Exports;

use App\Models\StockAdjustment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAdjustmentExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = StockAdjustment::query()->with(['warehouse', 'inventoryStocktake']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['adjustment_type'])) {
            $query->where('adjustment_type', $this->filters['adjustment_type']);
        }

        if (! empty($this->filters['inventory_stocktake_id'])) {
            $query->where('inventory_stocktake_id', $this->filters['inventory_stocktake_id']);
        }

        if (! empty($this->filters['adjustment_date_from'])) {
            $query->whereDate('adjustment_date', '>=', $this->filters['adjustment_date_from']);
        }

        if (! empty($this->filters['adjustment_date_to'])) {
            $query->whereDate('adjustment_date', '<=', $this->filters['adjustment_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['adjustment_number', 'warehouse_id', 'adjustment_date', 'adjustment_type', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Adjustment Number',
            'Warehouse',
            'Adjustment Date',
            'Adjustment Type',
            'Status',
            'Stocktake Number',
            'Created At',
        ];
    }

    public function map($stockAdjustment): array
    {
        return [
            $stockAdjustment->id,
            $stockAdjustment->adjustment_number,
            $stockAdjustment->warehouse?->name,
            $stockAdjustment->adjustment_date?->toDateString(),
            $stockAdjustment->adjustment_type,
            $stockAdjustment->status,
            $stockAdjustment->inventoryStocktake?->stocktake_number,
            $stockAdjustment->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
