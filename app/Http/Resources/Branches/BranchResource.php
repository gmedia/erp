<?php

namespace App\Http\Resources\Branches;

use App\Http\Resources\SimpleCrudResource;
use Illuminate\Http\Request;

class BranchResource extends SimpleCrudResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'company_id' => $this->resource->company_id,
        ]);
    }
}
