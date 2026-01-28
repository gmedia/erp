<?php

namespace Tests\Unit\Requests\Positions;

use App\Http\Requests\Positions\ExportPositionRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportPositionRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportPositionRequest::class;
    }
}
