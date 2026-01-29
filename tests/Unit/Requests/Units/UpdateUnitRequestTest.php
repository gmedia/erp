<?php

namespace Tests\Unit\Requests\Units;

use App\Http\Requests\Units\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudUpdateRequestTestTrait;

class UpdateUnitRequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudUpdateRequestTestTrait;

    protected function getRequestClass(): string
    {
        return UpdateUnitRequest::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getRouteParameterName(): string
    {
        return 'unit';
    }
}
