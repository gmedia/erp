<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockAdjustmentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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

    public function map($item): array
    {
        return $this->mapExportRow($item, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (StockAdjustment $item): mixed => $item->id,
            'Adjustment Number' => fn (StockAdjustment $item): mixed => $item->adjustment_number,
            'Warehouse' => fn (StockAdjustment $item): mixed => $this->relatedAttribute($item, 'warehouse', 'name'),
            'Adjustment Date' => fn (StockAdjustment $item): mixed => $this->formatDateValue(
                $item->adjustment_date,
                'Y-m-d',
            ),
            'Adjustment Type' => fn (StockAdjustment $item): mixed => $item->adjustment_type,
            'Status' => fn (StockAdjustment $item): mixed => $item->status,
            'Stocktake Number' => fn (StockAdjustment $item): mixed => $this->relatedAttribute(
                $item,
                'inventoryStocktake',
                'stocktake_number',
            ),
            'Created At' => fn (StockAdjustment $item): mixed => $this->formatIso8601($item->created_at),
        ];
    }
}
