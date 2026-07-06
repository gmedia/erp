<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductExport extends BaseExport
{
    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'unit', 'branch']);

        $this->applyConfiguredFilters($query, $this->filters, ['name', 'code', 'description'], [
            'product_category_id' => 'product_category_id',
            'unit_id' => 'unit_id',
            'branch_id' => 'branch_id',
            'type' => 'type',
            'status' => 'status',
            'billing_model' => 'billing_model',
        ], [], ['code', 'name', 'type', 'cost', 'selling_price', 'status', 'created_at']);

        return $query;
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
