<?php

namespace App\Actions\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait RecreatesItems
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  callable(array<string, mixed>): array<string, mixed>  $normalizer
     * @return array<int, array<string, mixed>>
     */
    protected function recreateItems(HasMany $relation, array $items, callable $normalizer): array
    {
        $normalized = Collection::make($items)
            ->map($normalizer)
            ->values()
            ->all();

        $relation->delete();
        $relation->createMany($normalized);

        return $normalized;
    }
}
