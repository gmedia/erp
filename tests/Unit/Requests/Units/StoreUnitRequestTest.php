<?php

namespace Tests\Unit\Requests\Units;

use App\Http\Requests\Units\StoreUnitRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudStoreRequestTestTrait;

class StoreUnitRequestTest extends TestCase
{
    use RefreshDatabase, SimpleCrudStoreRequestTestTrait;

    protected function getRequestClass(): string
    {
        return StoreUnitRequest::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
