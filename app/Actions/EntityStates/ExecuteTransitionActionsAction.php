<?php

namespace App\Actions\EntityStates;

use App\Models\PipelineTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ExecuteTransitionActionsAction
{
    /**
     * Execute all actions defined for a specific transition on a given entity.
     * 
     * @param PipelineTransition $transition The transition being executed
     * @param Model $entity The target entity
     * @return array Results of the executed actions
     * @throws \Exception if an action fails and on_failure policy is 'abort'
     */
    public function execute(PipelineTransition $transition, Model $entity): array
    {
        $actions = $transition->actions()->get(); // already ordered in relation
        $results = [];

        foreach ($actions as $actionDefinition) {
            $type = $actionDefinition->action_type;
            $params = $actionDefinition->config ?? [];
            $onFailure = $actionDefinition->on_failure ?? 'abort';

            try {
                $result = match ($type) {
                    'update_field' => $this->handleUpdateField($entity, $params),
                    'create_record' => $this->handleCreateRecord($entity, $params),
                    'send_notification' => $this->handleSendNotification($entity, $params),
                    'dispatch_job' => $this->handleDispatchJob($entity, $params),
                    'custom' => $this->handleCustomAction($entity, $params),
                    default => throw new \Exception("Unsupported transition action type: {$type}"),
                };
                
                $results[$actionDefinition->id] = ['status' => 'success', 'result' => $result];
            } catch (\Exception $e) {
                Log::error("Pipeline transition action failed. Action ID: {$actionDefinition->id}, Type: {$type}. Error: {$e->getMessage()}");
                
                $results[$actionDefinition->id] = ['status' => 'failed', 'error' => $e->getMessage()];
                
                if ($onFailure === 'abort') {
                    throw $e; // Re-throw to abort the entire transaction
                } elseif ($onFailure === 'log_and_continue') {
                    // Already logged above, just continue
                    continue;
                }
                // 'continue' just ignores it
            }
        }

        return $results;
    }

    private function handleUpdateField(Model $entity, array $params): bool
    {
        if (!isset($params['field']) || !isset($params['value'])) {
            throw new \InvalidArgumentException("Missing 'field' or 'value' for update_field action.");
        }

        $field = $params['field'];
        $value = $params['value'];

        $entity->$field = $value;
        return $entity->save(); // Saves the model with the updated field
    }

    private function handleCreateRecord(Model $entity, array $params): bool
    {
        // MVP: Not implemented fully. Log a warning
        Log::warning("'create_record' transition action requested, but not fully implemented in MVP. Entity: {$entity->getMorphClass()} ({$entity->id})");
        return true;
    }

    private function handleSendNotification(Model $entity, array $params): bool
    {
        // MVP: Not implemented fully. Log a warning
        Log::warning("'send_notification' transition action requested, but not fully implemented in MVP. Entity: {$entity->getMorphClass()} ({$entity->id})");
        return true;
    }

    private function handleDispatchJob(Model $entity, array $params): bool
    {
        // MVP: Not implemented fully. Log a warning
        Log::warning("'dispatch_job' transition action requested, but not fully implemented in MVP. Entity: {$entity->getMorphClass()} ({$entity->id})");
        return true;
    }

    private function handleCustomAction(Model $entity, array $params): mixed
    {
        if (!isset($params['class']) || !isset($params['method'])) {
            throw new \InvalidArgumentException("Missing 'class' or 'method' for custom action.");
        }

        $class = $params['class'];
        $method = $params['method'];

        if (!class_exists($class)) {
            throw new \Exception("Custom action class not found: {$class}");
        }

        $instance = new $class();
        if (!method_exists($instance, $method)) {
            throw new \Exception("Custom action method not found: {$class}::{$method}");
        }

        // Call the custom action, passing the entity and optionally other params
        return $instance->$method($entity, $params['data'] ?? []);
    }
}
