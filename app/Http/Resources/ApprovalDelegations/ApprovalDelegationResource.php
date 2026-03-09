<?php

namespace App\Http\Resources\ApprovalDelegations;

use App\Models\ApprovalDelegation;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ApprovalDelegation $resource
 */
/**
 * @mixin \App\Models\ApprovalDelegation
 */
class ApprovalDelegationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'delegator' => [
                'id' => $this->resource->delegator_user_id,
                'name' => $this->resource->delegator?->name,
            ],
            'delegate' => [
                'id' => $this->resource->delegate_user_id,
                'name' => $this->resource->delegate?->name,
            ],
            'approvable_type' => $this->resource->approvable_type,
            'start_date' => $this->resource->start_date?->toIso8601String(),
            'end_date' => $this->resource->end_date?->toIso8601String(),
            'reason' => $this->resource->reason,
            'is_active' => $this->resource->is_active,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
