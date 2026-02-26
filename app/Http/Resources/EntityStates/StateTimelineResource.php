<?php

namespace App\Http\Resources\EntityStates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateTimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_state' => $this->fromState ? [
                'name' => $this->fromState->name,
                'color' => $this->fromState->color,
                'icon' => $this->fromState->icon,
            ] : null,
            'to_state' => $this->toState ? [
                'name' => $this->toState->name,
                'color' => $this->toState->color,
                'icon' => $this->toState->icon,
            ] : null,
            'transition' => $this->transition ? [
                'name' => $this->transition->name,
            ] : null,
            'performed_by' => $this->performedBy ? [
                'name' => $this->performedBy->name,
            ] : null,
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
