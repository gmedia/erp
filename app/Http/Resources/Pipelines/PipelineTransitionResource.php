<?php

namespace App\Http\Resources\Pipelines;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineTransitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pipeline_id' => $this->pipeline_id,
            'from_state_id' => $this->from_state_id,
            'from_state' => new PipelineStateResource($this->whenLoaded('fromState')),
            'to_state_id' => $this->to_state_id,
            'to_state' => new PipelineStateResource($this->whenLoaded('toState')),
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'required_permission' => $this->required_permission,
            'guard_conditions' => $this->guard_conditions ?? [],
            'requires_confirmation' => $this->requires_confirmation,
            'requires_comment' => $this->requires_comment,
            'requires_approval' => $this->requires_approval,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'actions' => PipelineTransitionActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
