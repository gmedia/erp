<?php

namespace App\Actions;

use App\Models\Position;

class CreatePositionAction
{
    /**
     * Execute the action to create a new position.
     *
     * @param array $data
     * @return Position
     */
    public function execute(array $data): Position
    {
        return Position::create($data);
    }
}
