<?php

namespace App\Actions\Departments;

use App\Models\Department;

class UpdateDepartmentAction
{
    /**
     * Execute the action to update an existing department.
     *
     * @param  array{name?: string}  $data
     */
    public function execute(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }
}
