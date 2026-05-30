<?php

namespace Tests\Unit\Actions\EntityStates\Fixtures;

use Illuminate\Database\Eloquent\Model;

class CustomTransitionAction
{
    public static array $calls = [];

    public function run(Model $entity, array $data): string
    {
        self::$calls[] = ['entity_id' => $entity->getKey(), 'data' => $data];

        return 'custom-ok';
    }
}
