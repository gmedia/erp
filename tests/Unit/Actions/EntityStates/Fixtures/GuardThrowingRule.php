<?php

namespace Tests\Unit\Actions\EntityStates\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class GuardThrowingRule
{
    public function evaluate(Model $entity, $transition): bool
    {
        throw new RuntimeException('Boom from guard rule');
    }
}
