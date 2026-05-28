<?php

namespace App\Http\Resources\AssetModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class AssetModelCollection extends ResourceCollection
{
    public $collects = AssetModelResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
