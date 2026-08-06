<?php

namespace Tests\Unit\Traits;

use App\Traits\HandlesConditions;
use Illuminate\Database\Eloquent\Model;

class HandlesConditionsHarness
{
    use HandlesConditions;

    public function runEvaluateConditions(array $conditions, Model $entity): bool
    {
        return $this->evaluateConditions($conditions, $entity);
    }

    public function runEvaluateFieldCheck(array $check, Model $entity): bool
    {
        return $this->evaluateFieldCheck($check, $entity);
    }

    public function runEvaluateRelationCheck(array $check, Model $entity): bool
    {
        return $this->evaluateRelationCheck($check, $entity);
    }
}
