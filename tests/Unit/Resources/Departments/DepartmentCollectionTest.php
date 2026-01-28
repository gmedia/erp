<?php

namespace Tests\Unit\Resources\Departments;

use App\Http\Resources\Departments\DepartmentCollection;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class DepartmentCollectionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return DepartmentCollection::class;
    }

    protected function getModelClass(): string
    {
        return Department::class;
    }
}
