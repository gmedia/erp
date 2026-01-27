<?php

namespace App\Http\Requests\Departments;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Department;

class UpdateDepartmentRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return Department::class;
    }
}
