<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\StockAdjustment;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentExport extends BaseExport
{
    public function __construct(protected readonly array $filters = []) {}

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
