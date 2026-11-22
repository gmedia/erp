<?php

namespace App\Http\Resources\ApprovalDelegations;

use App\Models\ApprovalDelegation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ApprovalDelegation $resource
 */
/**
 * @mixin ApprovalDelegation
 */
class ApprovalDelegationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
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
            'status' => $this->derivedStatus(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Status turunan dari tanggal: delegasi yang belum mulai tidak boleh
     * tampil "Active" (W5-17).
     */
    private function derivedStatus(): string
    {
        if (! $this->resource->is_active) {
            return 'inactive';
        }

        $today = now()->startOfDay();
        $start = $this->resource->start_date;
        $end = $this->resource->end_date;

        if ($start !== null && $start->gt($today)) {
            return 'scheduled';
        }

        if ($end !== null && $end->lt($today)) {
            return 'expired';
        }

        return 'active';
    }
}
