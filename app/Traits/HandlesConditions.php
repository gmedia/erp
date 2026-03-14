<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait HandlesConditions
{
    /**
     * Evaluate field and relation checks against an entity.
     */
    protected function evaluateConditions(array $conditions, Model $entity): bool
    {
        // 1. Evaluate field_checks
        if (isset($conditions['field_checks']) && is_array($conditions['field_checks'])) {
            foreach ($conditions['field_checks'] as $check) {
                if (! $this->evaluateFieldCheck($check, $entity)) {
                    return false;
                }
            }
        }

        // 2. Evaluate relation_checks
        if (isset($conditions['relation_checks']) && is_array($conditions['relation_checks'])) {
            foreach ($conditions['relation_checks'] as $check) {
                if (! $this->evaluateRelationCheck($check, $entity)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluate a single field check.
     */
    protected function evaluateFieldCheck(array $check, Model $entity): bool
    {
        $field = $check['field'] ?? null;
        $operator = $check['operator'] ?? 'equals';
        $expectedValue = $check['value'] ?? null;

        if (! $field) {
            return true;
        }

        $actualValue = $entity->$field;

        return match ($operator) {
            'equals', '=' => $actualValue == $expectedValue,
            'not_equals', '!=' => $actualValue != $expectedValue,
            'greater_than', '>' => $actualValue > $expectedValue,
            'less_than', '<' => $actualValue < $expectedValue,
            'greater_than_or_equal', '>=' => $actualValue >= $expectedValue,
            'less_than_or_equal', '<=' => $actualValue <= $expectedValue,
            'contains' => str_contains((string) $actualValue, (string) $expectedValue),
            'not_null' => ! is_null($actualValue),
            'is_null' => is_null($actualValue),
            'in' => is_array($expectedValue)
                ? in_array($actualValue, $expectedValue)
                : ($actualValue == $expectedValue),
            'not_in' => is_array($expectedValue)
                ? ! in_array($actualValue, $expectedValue)
                : ($actualValue != $expectedValue),
            default => false,
        };
    }

    /**
     * Evaluate a single relation check.
     */
    protected function evaluateRelationCheck(array $check, Model $entity): bool
    {
        $relation = $check['relation'] ?? null;
        $field = $check['field'] ?? null;
        $operator = $check['operator'] ?? 'equals';
        $expectedValue = $check['value'] ?? null;

        if (! $relation || ! $field) {
            return true;
        }

        // Load relation if not loaded
        if (! $entity->relationLoaded($relation)) {
            $entity->load($relation);
        }

        $relatedModel = $entity->getRelation($relation);

        if (! $relatedModel) {
            return false;
        }

        $actualValue = $relatedModel->$field;

        return match ($operator) {
            'equals', '=' => $actualValue == $expectedValue,
            'not_equals', '!=' => $actualValue != $expectedValue,
            'greater_than', '>' => $actualValue > $expectedValue,
            'less_than', '<' => $actualValue < $expectedValue,
            'greater_than_or_equal', '>=' => $actualValue >= $expectedValue,
            'less_than_or_equal', '<=' => $actualValue <= $expectedValue,
            'not_null' => ! is_null($actualValue),
            'is_null' => is_null($actualValue),
            'in' => is_array($expectedValue)
                ? in_array($actualValue, $expectedValue)
                : ($actualValue == $expectedValue),
            'not_in' => is_array($expectedValue)
                ? ! in_array($actualValue, $expectedValue)
                : ($actualValue != $expectedValue),
            default => false,
        };
    }
}
