<?php

namespace Tests\Unit\Requests\Departments;

use App\Http\Requests\Departments\IndexDepartmentRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudIndexRequestTestTrait;

class IndexDepartmentRequestTest extends TestCase
{
    use SimpleCrudIndexRequestTestTrait;

    protected function getRequestClass(): string
    {
        return IndexDepartmentRequest::class;
    }
}
