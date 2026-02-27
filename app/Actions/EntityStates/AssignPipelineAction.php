<?php

namespace App\Actions\EntityStates;

use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssignPipelineAction
{
    /**
     * Assign an entity to its appropriate pipeline and set its initial state.
     * 
     * @param Model $entity The entity (e.g., Asset)
     * @return PipelineEntityState|null The newly created state, or null if no pipeline found
     */
    public function execute(Model $entity): ?PipelineEntityState
    {
        // 1. Find the appropriate pipeline structure for this entity type
        $entityType = $entity->getMorphClass();
        
        $pipeline = Pipeline::where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();

        if (!$pipeline) {
            return null; // No active pipeline configured for this entity type
        }

        // 2. See if the entity is already in a pipeline state
        $existingState = PipelineEntityState::where('entity_type', $entityType)
            ->where('entity_id', $entity->id)
            ->where('pipeline_id', $pipeline->id)
            ->first();

        if ($existingState) {
            return $existingState;
        }

        // 3. Find the initial state for the configured pipeline
        $initialState = $pipeline->states()->where('type', 'initial')->first();

        if (!$initialState) {
            return null; // Pipeline is misconfigured (no initial state)
        }

        return DB::transaction(function () use ($pipeline, $entityType, $entity, $initialState) {
            // 4. Create the entity state record
            $entityState = PipelineEntityState::create([
                'pipeline_id' => $pipeline->id,
                'entity_type' => $entityType,
                'entity_id' => $entity->id,
                'current_state_id' => $initialState->id,
                'last_transitioned_by' => auth()->id(),
                'last_transitioned_at' => now(),
            ]);

            // 5. Create the initial log entry
            $entity->pipelineStateLogs()->create([
                'pipeline_entity_state_id' => $entityState->id,
                'to_state_id' => $initialState->id,
                'performed_by' => auth()->id(),
                'comment' => 'Initial pipeline assignment',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $entityState;
        });
    }
}
