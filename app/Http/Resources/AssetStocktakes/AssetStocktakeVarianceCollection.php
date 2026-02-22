<?php

namespace App\Http\Resources\AssetStocktakes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AssetStocktakeVarianceCollection extends ResourceCollection
{
    public $collects = AssetStocktakeVarianceResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
