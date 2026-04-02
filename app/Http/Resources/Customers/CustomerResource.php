<?php

namespace App\Http\Resources\Customers;

use App\Http\Resources\Concerns\BuildsPartyResourceData;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Customer $resource
 */
/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
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
        return $this->partyResourceData(includeNotes: true);
    }
}
