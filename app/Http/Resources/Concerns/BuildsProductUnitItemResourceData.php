<?php

namespace App\Http\Resources\Concerns;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

trait BuildsProductUnitItemResourceData
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function productUnitItemResourceData(Model $item, array $attributes): array
    {
        /** @var Product|null $product */
        $product = $item->product;
        /** @var Unit|null $unit */
        $unit = $item->unit;

        return array_merge([
            'id' => $item->id,
            'product' => [
                'id' => $item->product_id,
                'name' => $product?->name,
            ],
            'unit' => [
                'id' => $item->unit_id,
                'name' => $unit?->name,
            ],
        ], $attributes);
    }
}
