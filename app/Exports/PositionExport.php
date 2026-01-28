<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Position;

/**
 * Export class for positions.
 */
class PositionExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Position::class;
    }
}
