<?php

namespace App\Http\Resources\ApprovalAuditTrail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ApprovalAuditTrailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_request_id' => $this->approval_request_id,
            'approvable_type' => $this->approvable_type,
            'approvable_type_short' => Str::afterLast($this->approvable_type, '\\'),
            'approvable_id' => $this->approvable_id,
            'event' => $this->event,
            'actor_user_id' => $this->actor_user_id,
            'actor_user_name' => $this->actor?->name ?? 'System',
            'step_order' => $this->step_order,
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
