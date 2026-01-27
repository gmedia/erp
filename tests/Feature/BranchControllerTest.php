<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = Branch::class;
    protected $endpoint = '/api/branches';
    protected $permissionPrefix = 'branch';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
