<?php

namespace App\Http\Resources\Pipelines;

use Illuminate\Http\Resources\Json\ResourceCollection;

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
