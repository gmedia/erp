<?php

namespace App\Http\Requests\Departments;

use App\Models\Department;
use App\Http\Requests\SimpleCrudStoreRequest;

class StoreDepartmentRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Department::class;
    }
}
