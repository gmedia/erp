<?php

namespace App\Http\Resources\EntityStates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'pipeline' => [
                'id' => $this->pipeline->id,
                'name' => $this->pipeline->name,
                'code' => $this->pipeline->code,
            ],
            'current_state' => [
                'id' => $this->currentState->id,
                'code' => $this->currentState->code,
                'name' => $this->currentState->name,
                'type' => $this->currentState->type,
                'color' => $this->currentState->color,
                'icon' => $this->currentState->icon,
            ],
            'last_transitioned_at' => $this->last_transitioned_at,
            'last_transitioned_by' => $this->lastTransitionedBy ? [
                'id' => $this->lastTransitionedBy->id,
                'name' => $this->lastTransitionedBy->name,
            ] : null,
            // Available transitions are typically attached dynamically by the controller
            'available_transitions' => $this->when(isset($this->available_transitions), $this->available_transitions),
        ];
    }
}
