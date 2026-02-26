<?php

namespace App\Http\Controllers;

use App\Actions\EntityStates\AssignPipelineAction;
use App\Actions\EntityStates\EvaluateGuardAction;
use App\Actions\EntityStates\ExecuteTransitionAction;
use App\Http\Requests\EntityStates\ExecuteTransitionRequest;
use App\Http\Resources\EntityStates\EntityStateResource;
use App\Http\Resources\EntityStates\StateTimelineResource;
use App\Models\PipelineEntityState;
use App\Models\PipelineTransition;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;

class EntityStateController extends Controller
{
    public function __construct(
        protected AssignPipelineAction $assignPipelineAction,
        protected EvaluateGuardAction $evaluateGuardAction,
        protected ExecuteTransitionAction $executeTransitionAction
    ) {}

    /**
     * Helper to resolve entity instance from type and id
     */
    protected function resolveEntity(string $entityType, int|string $entityId)
    {
        $modelClass = Relation::getMorphedModel($entityType);
        
        if (!$modelClass) {
            // Fallback: try to guess the class (e.g. 'asset' -> 'App\Models\Asset')
            $studlyType = str($entityType)->studly();
            $guessedClass = "App\\Models\\{$studlyType}";
            if (class_exists($guessedClass)) {
                $modelClass = $guessedClass;
            } else {
                abort(404, "Unknown entity type: {$entityType}");
            }
        }

        $entity = app($modelClass)->where('ulid', $entityId)->first() ?? app($modelClass)->find($entityId);
        
        if (!$entity) {
            abort(404, "Entity not found");
        }
        
        return $entity;
    }

    /**
     * Get the current pipeline state and available transitions for an entity.
     */
    public function getState(string $entityType, string $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);

        // Auto-assign pipeline if entity doesn't have one
        if (!method_exists($entity, 'pipelineEntityState')) {
             abort(400, "Entity type {$entityType} does not support pipelines.");
        }

        $entityState = $entity->pipelineEntityState()->with(['pipeline', 'currentState', 'lastTransitionedBy'])->first();

        if (!$entityState) {
            $entityState = $this->assignPipelineAction->execute($entity);
            if (!$entityState) {
                return response()->json(['message' => 'No active pipeline configuration found for this entity type.'], 404);
            }
            $entityState->load(['pipeline', 'currentState', 'lastTransitionedBy']);
        }

        // Load available transitions for the current state
        $transitions = PipelineTransition::where('pipeline_id', $entityState->pipeline_id)
            ->where('from_state_id', $entityState->current_state_id)
            ->with(['toState'])
            ->orderBy('sort_order')
            ->get();

        // Check guards and permissions for each transition
        $availableTransitions = $transitions->map(function ($transition) use ($entity) {
            // Check permission
            $hasPermission = !$transition->required_permission || auth()->user()?->employee?->hasPermission($transition->required_permission);
            
            // Evaluate Guards
            $guardFailures = [];
            if ($hasPermission) {
                $guardFailures = $this->evaluateGuardAction->execute($transition, $entity);
            }

            return [
                'id' => $transition->id,
                'name' => $transition->name,
                'description' => $transition->description,
                'to_state' => [
                    'id' => $transition->toState->id,
                    'name' => $transition->toState->name,
                    'color' => $transition->toState->color,
                    'icon' => $transition->toState->icon,
                ],
                'requires_comment' => $transition->requires_comment,
                'requires_confirmation' => $transition->requires_confirmation,
                'is_allowed' => $hasPermission && empty($guardFailures),
                'rejection_reasons' => $hasPermission ? $guardFailures : ['You do not have permission to execute this transition.'],
            ];
        });

        // Attach transitions to the resource
        $entityState->available_transitions = $availableTransitions;

        return (new EntityStateResource($entityState))->response();
    }

    /**
     * Execute a transition on an entity.
     */
    public function executeTransition(ExecuteTransitionRequest $request, string $entityType, string $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        $entityState = $entity->pipelineEntityState()->first();
        if (!$entityState) {
            abort(404, "Entity state not found");
        }

        $transition = PipelineTransition::findOrFail($request->validated('transition_id'));

        $updatedState = $this->executeTransitionAction->execute(
            $entityState,
            $transition,
            $entity,
            $request->validated('comment'),
            $request->validated('metadata') ?? []
        );

        return response()->json([
            'message' => 'Transition executed successfully',
            'data' => new EntityStateResource($updatedState)
        ]);
    }

    /**
     * Get the state transition timeline logs.
     */
    public function getTimeline(string $entityType, string $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!method_exists($entity, 'pipelineStateLogs')) {
             abort(400, "Entity type {$entityType} does not support pipelines.");
        }

        $logs = $entity->pipelineStateLogs()
            ->with(['fromState', 'toState', 'transition', 'performedBy'])
            ->paginate(15);

        return StateTimelineResource::collection($logs)->response();
    }
}
