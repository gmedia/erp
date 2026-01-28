<?php

namespace Tests\Unit\Domain\Positions;

use App\Domain\Positions\PositionFilterService;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudFilterServiceTestTrait;

class PositionFilterServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudFilterServiceTestTrait;

    protected function getFilterServiceClass(): string
    {
        return PositionFilterService::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }
}
