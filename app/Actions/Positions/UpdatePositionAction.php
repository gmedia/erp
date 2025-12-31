<?php

namespace App\Actions\Positions;

use App\Models\Position;

class UpdatePositionAction
{
    /**
     * Execute the action to update an existing position.
     *
     * @param  array{name?: string}  $data
     */
    public function execute(Position $position, array $data): Position
    {
        $position->update($data);

        return $position->fresh();
    }
}
