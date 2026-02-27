<?php

namespace App\Actions\EntityStates;

use App\Models\PipelineTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EvaluateGuardAction
{
    /**
     * Evaluate the guard conditions for a specific transition on a given entity.
     * 
     * @param PipelineTransition $transition The proposed transition
     * @param Model $entity The target entity
     * @return array Array of failure messages (empty means all guards passed)
     */
    public function execute(PipelineTransition $transition, Model $entity): array
    {
        $failures = [];
        $guards = $transition->guard_conditions;

        if (empty($guards)) {
            return []; // No guards, open transition
        }

        // 1. Evaluate field_checks: [ {"field": "status", "operator": "equals", "value": "active"} ]
        if (isset($guards['field_checks']) && is_array($guards['field_checks'])) {
            foreach ($guards['field_checks'] as $check) {
                $field = $check['field'] ?? null;
                $operator = $check['operator'] ?? 'equals';
                $expectedValue = $check['value'] ?? null;

                if (!$field) continue;

                $actualValue = $entity->$field;

                $passed = match ($operator) {
                    'equals', '=' => $actualValue == $expectedValue,
                    'not_equals', '!=' => $actualValue != $expectedValue,
                    'greater_than', '>' => $actualValue > $expectedValue,
                    'less_than', '<' => $actualValue < $expectedValue,
                    'contains' => str_contains((string)$actualValue, (string)$expectedValue),
                    'not_null' => !is_null($actualValue),
                    'is_null' => is_null($actualValue),
                    default => false,
                };

                if (!$passed) {
                    $failures[] = "Field check failed: {$field} must " . str_replace('_', ' ', $operator) . " '{$expectedValue}' (current value: '{$actualValue}')";
                }
            }
        }

        // 2. Evaluate relation_checks: [ {"relation": "category", "field": "code", "operator": "equals", "value": "IT"} ]
        if (isset($guards['relation_checks']) && is_array($guards['relation_checks'])) {
            foreach ($guards['relation_checks'] as $check) {
                $relation = $check['relation'] ?? null;
                $field = $check['field'] ?? null;
                $operator = $check['operator'] ?? 'equals';
                $expectedValue = $check['value'] ?? null;

                if (!$relation || !$field) continue;

                // Load relation if not loaded
                if (!$entity->relationLoaded($relation)) {
                    $entity->load($relation);
                }

                $relatedModel = $entity->getRelation($relation);
                
                if (!$relatedModel) {
                    $failures[] = "Relation check failed: relation '{$relation}' is empty";
                    continue;
                }

                $actualValue = $relatedModel->$field;

                $passed = match ($operator) {
                    'equals', '=' => $actualValue == $expectedValue,
                    'not_equals', '!=' => $actualValue != $expectedValue,
                    'not_null' => !is_null($actualValue),
                    'is_null' => is_null($actualValue),
                    default => false,
                };

                if (!$passed) {
                    $failures[] = "Relation check failed: {$relation}.{$field} must " . str_replace('_', ' ', $operator) . " '{$expectedValue}' (current value: '{$actualValue}')";
                }
            }
        }

        // 3. Evaluate custom_rule: "App\Rules\MyCustomRule" (must implement a specific interface or method)
        if (isset($guards['custom_rule']) && is_string($guards['custom_rule'])) {
            $ruleClass = $guards['custom_rule'];
            if (class_exists($ruleClass)) {
                try {
                    $rule = new $ruleClass();
                    if (method_exists($rule, 'evaluate')) {
                        $passed = $rule->evaluate($entity, $transition);
                        if (!$passed) {
                            $failures[] = "Custom rule check failed: {$ruleClass}";
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to evaluate custom pipeline guard rule: {$ruleClass}. Error: {$e->getMessage()}");
                    $failures[] = "Custom rule execution failed: {$ruleClass}";
                }
            } else {
                $failures[] = "Custom rule class not found: {$ruleClass}";
            }
        }

        return $failures;
    }
}
