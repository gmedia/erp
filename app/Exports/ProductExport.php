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

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'unit', 'branch']);

        $this->applyConfiguredFilters($query, $this->filters, ['name', 'code', 'description'], [
            'category_id' => 'category_id',
            'unit_id' => 'unit_id',
            'branch_id' => 'branch_id',
            'type' => 'type',
            'status' => 'status',
            'billing_model' => 'billing_model',
        ], [], ['code', 'name', 'type', 'cost', 'selling_price', 'status', 'created_at']);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($product): array
    {
        return $this->mapExportRow($product, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Product $product): mixed => $product->id,
            'Code' => fn (Product $product): mixed => $product->code,
            'Name' => fn (Product $product): mixed => $product->name,
            'Type' => fn (Product $product): mixed => $product->type,
            'Category' => fn (Product $product): mixed => $this->relatedAttribute($product, 'category', 'name'),
            'Unit' => fn (Product $product): mixed => $this->relatedAttribute($product, 'unit', 'name'),
            'Cost' => fn (Product $product): mixed => $product->cost,
            'Selling Price' => fn (Product $product): mixed => $product->selling_price,
            'Status' => fn (Product $product): mixed => $product->status,
            'Created At' => fn (Product $product): mixed => $this->formatIso8601($product->created_at),
        ];
    }
}
