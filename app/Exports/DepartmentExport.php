<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Department;

/**
 * Export class for departments.
 */
class DepartmentExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Department::class;
    }
}
