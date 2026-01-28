<?php

namespace Tests\Unit\Domain\Departments;

use App\Domain\Departments\DepartmentFilterService;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class DepartmentFilterServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return DepartmentFilterService::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }
}
