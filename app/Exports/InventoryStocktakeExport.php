<?php

namespace App\Exports;

use App\Models\InventoryStocktake;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryStocktakeExport implements FromQuery, WithHeadings, WithMapping, WithStyles
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
        $query = InventoryStocktake::query()->with(['warehouse', 'productCategory']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('stocktake_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        if (! empty($this->filters['product_category_id'])) {
            $query->where('product_category_id', $this->filters['product_category_id']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['stocktake_date_from'])) {
            $query->whereDate('stocktake_date', '>=', $this->filters['stocktake_date_from']);
        }

        if (! empty($this->filters['stocktake_date_to'])) {
            $query->whereDate('stocktake_date', '<=', $this->filters['stocktake_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['stocktake_number', 'warehouse_id', 'stocktake_date', 'status', 'product_category_id', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
