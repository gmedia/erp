<?php

namespace Tests\Unit\Requests\Units;

use App\Http\Requests\Units\ExportUnitRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportUnitRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportUnitRequest::class;
    }
}
