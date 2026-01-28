<?php

namespace Tests\Unit\Requests\CustomerCategories;

use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportCustomerCategoryRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportCustomerCategoryRequest::class;
    }
}
