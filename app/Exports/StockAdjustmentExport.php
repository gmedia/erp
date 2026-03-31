<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockAdjustmentExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = StockAdjustment::query()->with(['warehouse', 'inventoryStocktake']);

        $this->applySearchFilter($query, $this->filters, ['adjustment_number', 'notes']);
        $this->applyExactFilters($query, $this->filters, [
            'warehouse_id' => 'warehouse_id',
            'status' => 'status',
            'adjustment_type' => 'adjustment_type',
            'inventory_stocktake_id' => 'inventory_stocktake_id',
        ]);
        $this->applyDateRangeFilters($query, $this->filters, [
            'adjustment_date' => ['from' => 'adjustment_date_from', 'to' => 'adjustment_date_to'],
        ]);
        $this->applySorting($query, $this->filters, [
            'adjustment_number',
            'warehouse_id',
            'adjustment_date',
            'adjustment_type',
            'status',
            'created_at',
        ]);

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
}
