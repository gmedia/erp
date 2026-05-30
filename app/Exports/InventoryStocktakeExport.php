<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\InventoryStocktake;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class InventoryStocktakeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'ID' => fn (InventoryStocktake $item): mixed => $item->id,
            'Stocktake Number' => fn (InventoryStocktake $item): mixed => $item->stocktake_number,
            'Warehouse' => fn (InventoryStocktake $item): mixed => $this->relatedAttribute($item, 'warehouse', 'name'),
            'Stocktake Date' => fn (InventoryStocktake $item): mixed => $this->formatDateValue(
                $item->stocktake_date,
                'Y-m-d',
            ),
            'Status' => fn (InventoryStocktake $item): mixed => $item->status,
            'Product Category' => fn (InventoryStocktake $item): mixed => $this->relatedAttribute(
                $item,
                'productCategory',
                'name',
            ),
            'Completed At' => fn (InventoryStocktake $item): mixed => $this->formatIso8601($item->completed_at),
            'Created At' => fn (InventoryStocktake $item): mixed => $this->formatIso8601($item->created_at),
        ];
    }
}
