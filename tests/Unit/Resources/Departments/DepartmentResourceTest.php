<?php

namespace Tests\Unit\Resources\Departments;

use App\Http\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class DepartmentResourceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return DepartmentResource::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }
}
