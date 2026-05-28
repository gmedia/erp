<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

trait HandlesNestedItemsResponse
{
    /**
     * @param  Model&object{items: Collection<int, mixed>}  $entity
     */
    protected function nestedItemsResponse(Model $entity, array $relations, callable $mapper): JsonResponse
    {
        $entity->load($relations);

        return response()->json([
            'data' => $entity->items->map($mapper)->values(),
        ]);
    }
}
