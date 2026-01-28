<?php

namespace Tests\Unit\Requests\Positions;

use App\Http\Requests\Positions\StorePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StorePositionRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StorePositionRequest::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }
}
