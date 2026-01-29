<?php

namespace Tests\Unit\Resources\Units;

use App\Http\Resources\Units\UnitResource;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class UnitResourceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return UnitResource::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
