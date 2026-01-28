<?php

namespace Tests\Unit\Requests\Departments;

use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreDepartmentRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreDepartmentRequest::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }
}
