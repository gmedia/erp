<?php

namespace App\Http\Resources\AssetModels;

use Illuminate\Http\Resources\Json\ResourceCollection;

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
