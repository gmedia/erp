<?php

namespace Tests\Unit\Resources\Units;

use App\Http\Resources\Units\UnitCollection;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudCollectionTestTrait;

class UnitCollectionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudCollectionTestTrait;

    protected function getCollectionClass(): string
    {
        return UnitCollection::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
