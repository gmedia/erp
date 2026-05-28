<?php

namespace App\Http\Resources\Pipelines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class PipelineCollection extends ResourceCollection
{
    public $collects = PipelineResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
