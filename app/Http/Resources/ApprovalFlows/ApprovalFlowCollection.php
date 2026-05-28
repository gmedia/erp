<?php

namespace App\Http\Resources\ApprovalFlows;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
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
