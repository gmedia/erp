<?php

namespace Tests\Unit\Domain\Units;

use App\Domain\Units\UnitFilterService;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class UnitFilterServiceTest extends TestCase
{
    use RefreshDatabase, SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return UnitFilterService::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
