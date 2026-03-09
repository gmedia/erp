<?php

namespace App\Http\Requests\Departments;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Department;

class StoreDepartmentRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Department::class;
    }
}
