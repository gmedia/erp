<?php

namespace App\Actions;

use App\Models\Department;

class UpdateDepartmentAction
{
    /**
     * Execute the action to update an existing department.
     *
     * @param Department $department
     * @param array{name?: string} $data
     * @return Department
     */
    public function execute(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }
}
