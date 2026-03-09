<?php

namespace App\Http\Resources\AssetLocations;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class AssetLocationCollection extends ResourceCollection
{
    public $collects = AssetLocationResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
