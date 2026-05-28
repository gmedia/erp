<?php

namespace App\Http\Resources\Suppliers;

use App\Http\Resources\Concerns\BuildsPartyResourceData;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Supplier $resource
 */
/**
 * @mixin Supplier
 */
class SupplierResource extends JsonResource
{
    use BuildsPartyResourceData;

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return $this->partyResourceData();
    }
}
