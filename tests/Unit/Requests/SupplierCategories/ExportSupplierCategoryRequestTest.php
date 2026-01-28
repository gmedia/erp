<?php

namespace Tests\Unit\Requests\SupplierCategories;

use App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportRequestTestTrait;

class ExportSupplierCategoryRequestTest extends TestCase
{
    use SimpleCrudExportRequestTestTrait;

    protected function getRequestClass(): string
    {
        return ExportSupplierCategoryRequest::class;
    }
}
