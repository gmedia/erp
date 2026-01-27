<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = Department::class;
    protected $endpoint = '/api/departments';
    protected $permissionPrefix = 'department';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
