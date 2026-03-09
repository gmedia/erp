<?php

namespace App\Http\Resources\AssetStocktakes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class AssetStocktakeCollection extends ResourceCollection
{
    public $collects = AssetStocktakeResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
