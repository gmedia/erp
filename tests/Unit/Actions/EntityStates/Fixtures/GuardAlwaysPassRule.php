<?php

namespace Tests\Unit\Actions\EntityStates\Fixtures;

use Illuminate\Database\Eloquent\Model;

class GuardAlwaysPassRule
{
    public function evaluate(Model $entity, $transition): bool
    {
        return true;
    }
}
