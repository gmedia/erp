<?php

namespace Tests\Unit\Requests\Departments;

use App\Http\Requests\Departments\ExportDepartmentRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportDepartmentRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportDepartmentRequest::class;
    }
}
