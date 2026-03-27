<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HandlesNestedItemsResponse
{
    /**
     * @param  Model&object{items: \Illuminate\Support\Collection<int, mixed>}  $entity
     */
    protected function nestedItemsResponse(Model $entity, array $relations, callable $mapper): JsonResponse
    {
        $entity->load($relations);

        return response()->json([
            'data' => $entity->items->map($mapper)->values(),
        ]);
    }
}