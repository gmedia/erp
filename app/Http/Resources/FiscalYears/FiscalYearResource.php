<?php

namespace App\Http\Resources\FiscalYears;

use App\Http\Resources\SimpleCrudResource;

class FiscalYearResource extends SimpleCrudResource
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
            'start_date' => $this->resource->start_date?->toDateString(),
            'end_date' => $this->resource->end_date?->toDateString(),
            'status' => $this->resource->status,
        ]);
    }
}
