<?php

namespace Tests\Unit\Requests\Branches;

use App\Http\Requests\Branches\ExportBranchRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportBranchRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportBranchRequest::class;
    }
}
