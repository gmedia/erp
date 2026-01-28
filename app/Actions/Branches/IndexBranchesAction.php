<?php

namespace App\Actions\Branches;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\Branch;

/**
 * Action to retrieve paginated branches with filtering and sorting.
 */
class IndexBranchesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Branch::class;
    }
}
