<?php

namespace Tests\Unit\Actions\Units;

use App\Actions\Units\IndexUnitsAction;
use App\Http\Requests\Units\IndexUnitRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexActionTestTrait;

class IndexUnitsActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudIndexActionTestTrait;

    protected function getActionClass(): string
    {
        return IndexUnitsAction::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getRequestClass(): string
    {
        return IndexUnitRequest::class;
    }
}
