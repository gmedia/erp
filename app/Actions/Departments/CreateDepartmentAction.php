<?php

namespace App\Actions\Departments;

use App\Models\Department;

class CreateDepartmentAction
{
    /**
     * Execute the action to create a new department.
     */
    public function execute(array $data): Department
    {
        return Department::create($data);
    }
}
