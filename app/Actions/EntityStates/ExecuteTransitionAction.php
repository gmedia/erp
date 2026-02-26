<?php

namespace App\Actions\EntityStates;

use App\Models\PipelineEntityState;
use App\Models\PipelineTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecuteTransitionAction
{
    public function __construct(
        protected EvaluateGuardAction $evaluateGuardAction,
        protected ExecuteTransitionActionsAction $executeTransitionActionsAction
    ) {}

    /**
     * Execute a transition for a given entity state.
     * 
     * @param PipelineEntityState $entityState
     * @param PipelineTransition $transition
     * @param Model $entity
     * @param string|null $comment
     * @param array $metadata
     * @return PipelineEntityState The updated entity state
     * @throws ValidationException If transition is invalid
     */
    public function execute(
        PipelineEntityState $entityState,
        PipelineTransition $transition,
        Model $entity,
        ?string $comment = null,
        array $metadata = []
    ): PipelineEntityState {
        // 1. Validate the transition belongs to the entity's current pipeline schema
        if ($transition->pipeline_id !== $entityState->pipeline_id) {
            throw ValidationException::withMessages([
                'transition_id' => 'Transition does not belong to this pipeline.'
            ]);
        }

        // 2. Validate current state matches transition from_state
        if ($transition->from_state_id !== $entityState->current_state_id && $transition->from_state_id !== null) {
            throw ValidationException::withMessages([
                'transition_id' => 'Current state does not allow this transition.'
            ]);
        }

        // 3. Check specific permission if defined
        if ($transition->required_permission && !auth()->user()?->employee?->hasPermission($transition->required_permission)) {
            // Should be a 403 conceptually, but throwing as validation simplifies controller
            throw ValidationException::withMessages([
                'transition_id' => 'You do not have permission to execute this transition.'
            ]);
        }

        // 4. Evaluate Guards
        $guardFailures = $this->evaluateGuardAction->execute($transition, $entity);
        if (!empty($guardFailures)) {
            throw ValidationException::withMessages([
                'guards' => $guardFailures
            ]);
        }

        return DB::transaction(function () use ($entityState, $transition, $entity, $comment, $metadata) {
            $fromStateId = $entityState->current_state_id;

            // 5. Update the entity state
            $entityState->update([
                'current_state_id' => $transition->to_state_id,
                'last_transitioned_by' => auth()->id(),
                'last_transitioned_at' => now(),
            ]);

            // 6. Execute Side-Effect Actions
            $actionResults = $this->executeTransitionActionsAction->execute($transition, $entity);

            $logMetadata = array_merge($metadata, ['action_results' => $actionResults]);

            // 7. Create State Log
            $entity->pipelineStateLogs()->create([
                'pipeline_entity_state_id' => $entityState->id,
                'from_state_id' => $fromStateId,
                'to_state_id' => $transition->to_state_id,
                'transition_id' => $transition->id,
                'performed_by' => auth()->id(),
                'comment' => $comment,
                'metadata' => $logMetadata,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $entityState->fresh(['currentState']);
        });
    }
}
