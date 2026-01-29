<?php

namespace App\Http\Resources\Units;

use App\Http\Resources\SimpleCrudResource;

class UnitResource extends SimpleCrudResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'symbol' => $this->resource->symbol,
        ]);
    }
}
