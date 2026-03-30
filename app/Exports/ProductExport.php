<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ProductExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'unit', 'branch']);

        $this->applySearchFilter($query, $this->filters, ['name', 'code', 'description']);
        $this->applyExactFilters($query, $this->filters, [
            'category_id' => 'category_id',
            'unit_id' => 'unit_id',
            'branch_id' => 'branch_id',
            'type' => 'type',
            'status' => 'status',
            'billing_model' => 'billing_model',
        ]);
        $this->applySorting($query, $this->filters, ['code', 'name', 'type', 'cost', 'selling_price', 'status', 'created_at']);

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Name',
            'Type',
            'Category',
            'Unit',
            'Cost',
            'Selling Price',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->code,
            $product->name,
            $product->type,
            $product->category?->name,
            $product->unit?->name,
            $product->cost,
            $product->selling_price,
            $product->status,
            $product->created_at?->toIso8601String(),
        ];
    }
}
