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

        $this->applySearchFilter($query, $this->filters, ['stocktake_number', 'notes']);
        $this->applyExactFilters($query, $this->filters, [
            'warehouse_id' => 'warehouse_id',
            'product_category_id' => 'product_category_id',
            'status' => 'status',
        ]);
        $this->applyDateRangeFilters($query, $this->filters, [
            'stocktake_date' => ['from' => 'stocktake_date_from', 'to' => 'stocktake_date_to'],
        ]);
        $this->applySorting($query, $this->filters, [
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
        return [
            'ID',
            'Stocktake Number',
            'Warehouse',
            'Stocktake Date',
            'Status',
            'Product Category',
            'Completed At',
            'Created At',
        ];
    }

    public function map($inventoryStocktake): array
    {
        return [
            $inventoryStocktake->id,
            $inventoryStocktake->stocktake_number,
            $inventoryStocktake->warehouse?->name,
            $inventoryStocktake->stocktake_date?->toDateString(),
            $inventoryStocktake->status,
            $inventoryStocktake->productCategory?->name,
            $inventoryStocktake->completed_at?->toIso8601String(),
            $inventoryStocktake->created_at?->toIso8601String(),
        ];
    }
}
