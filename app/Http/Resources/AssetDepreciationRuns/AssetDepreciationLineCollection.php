<?php

namespace App\Http\Resources\AssetDepreciationRuns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class AssetDepreciationLineCollection extends ResourceCollection
{
    public $collects = AssetDepreciationLineResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
