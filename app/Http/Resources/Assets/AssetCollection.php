<?php

namespace App\Http\Resources\Assets;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AssetCollection extends ResourceCollection
{
    public $collects = AssetResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
