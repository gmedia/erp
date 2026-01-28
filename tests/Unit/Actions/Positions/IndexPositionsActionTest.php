<?php

namespace Tests\Unit\Actions\Positions;

use App\Actions\Positions\IndexPositionsAction;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexPositionsActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexPositionsAction::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getRequestClass(): string
    {
        return IndexPositionRequest::class;
    }
}
