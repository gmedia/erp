<?php

namespace App\Http\Resources\Warehouses;

use App\Http\Resources\SimpleCrudResource;

class WarehouseResource extends SimpleCrudResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'branch_id' => $this->resource->branch_id,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'code' => $this->resource->code,
        ]);
    }
}
