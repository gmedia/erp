<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\InventoryStocktake;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class InventoryStocktakeExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return Builder<InventoryStocktake>
     */
    public function query(): Builder
    {
        $query = InventoryStocktake::query()->with(['warehouse', 'productCategory']);

        $this->applyConfiguredFilters($query, $this->filters, ['stocktake_number', 'notes'], [
            'warehouse_id' => 'warehouse_id',
            'product_category_id' => 'product_category_id',
            'status' => 'status',
        ], [
            'stocktake_date' => ['from' => 'stocktake_date_from', 'to' => 'stocktake_date_to'],
        ], [
            'stocktake_number',
            'warehouse_id',
            'stocktake_date',
            'status',
            'product_category_id',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($inventoryStocktake): array
    {
        return $this->mapExportRow($inventoryStocktake, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->id,
            'Stocktake Number' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->stocktake_number,
            'Warehouse' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->warehouse?->name,
            'Stocktake Date' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->stocktake_date?->toDateString(),
            'Status' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->status,
            'Product Category' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->productCategory?->name,
            'Completed At' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->completed_at?->toIso8601String(),
            'Created At' => static fn (InventoryStocktake $inventoryStocktake): mixed => $inventoryStocktake->created_at?->toIso8601String(),
        ];
    }
}
