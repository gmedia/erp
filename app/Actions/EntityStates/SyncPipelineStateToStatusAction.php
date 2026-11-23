<?php

namespace App\Actions\EntityStates;

use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use Illuminate\Database\Eloquent\Model;

class SyncPipelineStateToStatusAction
{
    /**
     * Pindahkan state pipeline entitas ke state yang kodenya sama dengan status baru.
     * Tidak melakukan apa-apa bila entitas belum punya pipeline atau state tujuan tidak ada.
     */
    public function execute(Model $entity, string $status): void
    {
        $entityState = PipelineEntityState::query()
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', $entity->getKey())
            ->first();

        if (! $entityState) {
            return;
        }

        $targetState = PipelineState::query()
            ->where('pipeline_id', $entityState->pipeline_id)
            ->where('code', $status)
            ->first();

        if (! $targetState || $targetState->id === $entityState->current_state_id) {
            return;
        }

        $entityState->update([
            'current_state_id' => $targetState->id,
            'last_transitioned_at' => now(),
        ]);
    }
}
