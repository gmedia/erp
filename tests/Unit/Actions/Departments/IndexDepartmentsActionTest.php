<?php

namespace Tests\Unit\Actions\Departments;

use App\Actions\Departments\IndexDepartmentsAction;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexDepartmentsActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexDepartmentsAction::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getRequestClass(): string
    {
        return IndexDepartmentRequest::class;
    }
}
