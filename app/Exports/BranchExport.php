<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Branch;

/**
 * Export class for branches.
 */
class BranchExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
