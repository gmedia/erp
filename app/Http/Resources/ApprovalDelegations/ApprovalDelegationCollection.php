<?php

namespace App\Http\Resources\ApprovalDelegations;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApprovalDelegationCollection extends ResourceCollection
{
    /**
     * The resource that this collection transforms.
     *
     * @var string
     */
    public $collects = ApprovalDelegationResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
