<?php

namespace Tests\Unit\Resources\Positions;

use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudResourceTestTrait;

class PositionResourceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudResourceTestTrait;

    protected function getResourceClass(): string
    {
        return PositionResource::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }
}
