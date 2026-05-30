<?php

namespace Tests\Unit\Actions\EntityStates\Fixtures;

use Illuminate\Database\Eloquent\Model;

class GuardAlwaysFailRule
{
    public function evaluate(Model $entity, $transition): bool
    {
        return false;
    }
}
