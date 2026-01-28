<?php

namespace App\Actions\Departments;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\Department;

/**
 * Action to retrieve paginated departments with filtering and sorting.
 */
class IndexDepartmentsAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Department::class;
    }
}
