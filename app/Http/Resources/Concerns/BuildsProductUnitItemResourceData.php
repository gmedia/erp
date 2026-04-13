<?php

namespace App\Http\Resources\Concerns;

use App\Models\Product;
use App\Models\Unit;
use DateTimeInterface;
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

    /**
     * @param  iterable<int, Model>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function productUnitItemsResourceData(iterable $items, callable $attributesResolver): array
    {
        $resourceItems = [];

        foreach ($items as $item) {
            $resourceItems[] = $this->productUnitItemResourceData($item, array_merge(
                $attributesResolver($item),
                [
                    'created_at' => $this->iso8601Timestamp($item->getAttribute('created_at')),
                    'updated_at' => $this->iso8601Timestamp($item->getAttribute('updated_at')),
                ],
            ));
        }

        return $resourceItems;
    }

    protected function iso8601Timestamp(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : null;
    }
}
