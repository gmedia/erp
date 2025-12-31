<?php

namespace App\Actions;

use App\Models\Position;

class UpdatePositionAction
{
    /**
     * Execute the action to update an existing position.
     *
     * @param Position $position
     * @param array{name?: string} $data
     * @return Position
     */
    public function execute(Position $position, array $data): Position
    {
        $position->update($data);

        return $position->fresh();
    }
}
