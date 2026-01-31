<?php

namespace App\Http\Resources\CoaVersions;

use App\Http\Resources\SimpleCrudResource;

class CoaVersionResource extends SimpleCrudResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'fiscal_year_id' => $this->resource->fiscal_year_id,
            'fiscal_year' => [
                'id' => $this->resource->fiscalYear?->id,
                'name' => $this->resource->fiscalYear?->name,
            ],
            'status' => $this->resource->status,
        ]);
    }
}
