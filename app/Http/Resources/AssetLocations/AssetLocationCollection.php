<?php

namespace App\Http\Resources\AssetLocations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
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
