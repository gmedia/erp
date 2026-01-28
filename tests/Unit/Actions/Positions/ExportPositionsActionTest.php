<?php

namespace Tests\Unit\Actions\Positions;

use App\Actions\Positions\ExportPositionsAction;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportActionTestTrait;

class ExportPositionsActionTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportActionTestTrait;

    protected function getActionClass(): string
    {
        return ExportPositionsAction::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getRequestClass(): string
    {
        return ExportPositionRequest::class;
    }

    protected function getExpectedFilenamePrefix(): string
    {
        return 'positions';
    }
}
