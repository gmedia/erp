<?php

namespace Tests\Feature;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class UnitControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;

    protected $modelClass = Unit::class;
    protected $endpoint = '/api/units';
    protected $permissionPrefix = 'unit';
    protected $structure = ['id', 'name', 'symbol', 'created_at', 'updated_at'];
}
