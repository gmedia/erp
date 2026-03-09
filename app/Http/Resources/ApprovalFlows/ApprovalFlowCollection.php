<?php

namespace App\Http\Resources\ApprovalFlows;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
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
