<?php

namespace Tests\Unit\Actions\Units;

use App\Actions\Units\ExportUnitsAction;
use App\Http\Requests\Units\ExportUnitRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportUnitsActionTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportUnitsAction::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getRequestClass(): string
    {
        return ExportUnitRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'units';
    }
}
