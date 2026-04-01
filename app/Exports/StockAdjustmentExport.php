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

        $this->applyConfiguredFilters($query, $this->filters, ['adjustment_number', 'notes'], [
            'warehouse_id' => 'warehouse_id',
            'status' => 'status',
            'adjustment_type' => 'adjustment_type',
            'inventory_stocktake_id' => 'inventory_stocktake_id',
        ], [
            'adjustment_date' => ['from' => 'adjustment_date_from', 'to' => 'adjustment_date_to'],
        ], [
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
        return $this->exportHeadings($this->columns());
    }

    public function map($stockAdjustment): array
    {
        return $this->mapExportRow($stockAdjustment, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (StockAdjustment $stockAdjustment): mixed => $stockAdjustment->id,
            'Adjustment Number' => fn (StockAdjustment $stockAdjustment): mixed => $stockAdjustment->adjustment_number,
            'Warehouse' => fn (StockAdjustment $stockAdjustment): mixed => $this->relatedAttribute($stockAdjustment, 'warehouse', 'name'),
            'Adjustment Date' => fn (StockAdjustment $stockAdjustment): mixed => $this->formatDateValue($stockAdjustment->adjustment_date, 'Y-m-d'),
            'Adjustment Type' => fn (StockAdjustment $stockAdjustment): mixed => $stockAdjustment->adjustment_type,
            'Status' => fn (StockAdjustment $stockAdjustment): mixed => $stockAdjustment->status,
            'Stocktake Number' => fn (StockAdjustment $stockAdjustment): mixed => $this->relatedAttribute($stockAdjustment, 'inventoryStocktake', 'stocktake_number'),
            'Created At' => fn (StockAdjustment $stockAdjustment): mixed => $this->formatIso8601($stockAdjustment->created_at),
        ];
    }
}
