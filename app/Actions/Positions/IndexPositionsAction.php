<?php

namespace App\Actions\Positions;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\Position;

/**
 * Action to retrieve paginated positions with filtering and sorting.
 */
class IndexPositionsAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Position::class;
    }
}
