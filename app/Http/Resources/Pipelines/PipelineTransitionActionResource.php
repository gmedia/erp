<?php

namespace App\Http\Resources\Pipelines;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineTransitionActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pipeline_transition_id' => $this->pipeline_transition_id,
            'action_type' => $this->action_type,
            'execution_order' => $this->execution_order,
            'config' => $this->config ?? [],
            'is_async' => $this->is_async,
            'on_failure' => $this->on_failure,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
