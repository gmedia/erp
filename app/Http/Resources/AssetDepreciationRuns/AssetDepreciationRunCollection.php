<?php

namespace App\Http\Resources\AssetDepreciationRuns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class AssetDepreciationRunCollection extends ResourceCollection
{
    public $collects = AssetDepreciationRunResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
