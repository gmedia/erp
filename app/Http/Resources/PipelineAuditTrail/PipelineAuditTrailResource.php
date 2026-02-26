<?php

namespace App\Http\Resources\PipelineAuditTrail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PipelineAuditTrailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pipeline_entity_state_id' => $this->pipeline_entity_state_id,
            'entity_type' => $this->entity_type,
            'entity_type_short' => Str::afterLast($this->entity_type, '\\'),
            'entity_id' => $this->entity_id,
            'pipeline_name' => $this->pipelineEntityState?->pipeline?->name,
            'from_state_id' => $this->from_state_id,
            'from_state_name' => $this->fromState?->name,
            'from_state_color' => $this->fromState?->color,
            'to_state_id' => $this->to_state_id,
            'to_state_name' => $this->toState?->name,
            'to_state_color' => $this->toState?->color,
            'transition_id' => $this->transition_id,
            'transition_name' => $this->transition?->name,
            'performed_by' => $this->performed_by,
            'performed_by_name' => $this->performedBy?->name ?? 'System',
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
