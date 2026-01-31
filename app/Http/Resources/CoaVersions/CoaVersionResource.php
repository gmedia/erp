<?php

namespace App\Http\Resources\CoaVersions;

use Illuminate\Http\Resources\Json\JsonResource;

class CoaVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fiscal_year_id' => $this->fiscal_year_id,
            'fiscal_year' => [
                'id' => $this->fiscalYear?->id,
                'name' => $this->fiscalYear?->name,
            ],
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
