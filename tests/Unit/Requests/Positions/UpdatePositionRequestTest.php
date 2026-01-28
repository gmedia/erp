<?php

namespace Tests\Unit\Requests\Positions;

use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdatePositionRequestTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdatePositionRequest::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'position';
    }
}
