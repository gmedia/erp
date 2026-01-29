<?php

namespace Tests\Unit\Requests\ProductCategories;

use App\Http\Requests\ProductCategories\ExportProductCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportProductCategoryRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportProductCategoryRequest::class;
    }
}
