<?php

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait UpsertsItemsWithCleanup
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  callable(array<string, mixed>): array<string, mixed>  $attributesBuilder
     */
    protected function upsertItemsWithCleanup(
        HasMany $relation,
        array $items,
        string $itemKey,
        callable $attributesBuilder
    ): void {
        $foreignKey = $relation->getForeignKeyName();
        $parentKey = $relation->getParentKey();
        $relatedModel = $relation->getRelated();
        $itemIds = [];

        foreach ($items as $item) {
            $itemIds[] = (int) $item[$itemKey];

            $relatedModel::updateOrCreate(
                [
                    $foreignKey => $parentKey,
                    $itemKey => $item[$itemKey],
                ],
                $attributesBuilder($item),
            );
        }

        $relation
            ->when(! empty($itemIds), fn ($query) => $query->whereNotIn($itemKey, $itemIds))
            ->delete();
    }
}
