<?php

namespace App\Http\Resources\Suppliers;

use App\Http\Resources\Concerns\BuildsPartyResourceData;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Supplier $resource
 */
/**
 * @mixin \App\Models\Supplier
 */
class SupplierResource extends JsonResource
{
    use BuildsPartyResourceData;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return $this->partyResourceData();
    }
}
