<?php

namespace App\Http\Resources\ApprovalFlows;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApprovalFlowCollection extends ResourceCollection
{
    public $collects = ApprovalFlowResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
