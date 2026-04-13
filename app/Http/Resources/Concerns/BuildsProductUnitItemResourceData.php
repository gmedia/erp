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
        $productRelation = $item->getRelationValue('product');
        $unitRelation = $item->getRelationValue('unit');

        /** @var Product|null $product */
        $product = $productRelation instanceof Product ? $productRelation : null;
        /** @var Unit|null $unit */
        $unit = $unitRelation instanceof Unit ? $unitRelation : null;

        return array_merge([
            'id' => $item->getAttribute('id'),
            'product' => [
                'id' => $item->getAttribute('product_id'),
                'name' => $product?->name,
            ],
            'unit' => [
                'id' => $item->getAttribute('unit_id'),
                'name' => $unit?->name,
            ],
        ], $attributes);
    }
}
