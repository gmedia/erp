<?php

namespace Tests\Feature;

use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = Position::class;
    protected $endpoint = '/api/positions';
    protected $permissionPrefix = 'position';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
