<?php

namespace App\Actions\Positions;

use App\Models\Position;

class CreatePositionAction
{
    /**
     * Execute the action to create a new position.
     */
    public function execute(array $data): Position
    {
        return Position::create($data);
    }
}
