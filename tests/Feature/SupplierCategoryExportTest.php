<?php

namespace Tests\Feature;

use App\Exports\SupplierCategoryExport;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class SupplierCategoryExportTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return SupplierCategoryExport::class;
    }

    protected function getModelClass(): string
    {
        return SupplierCategory::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Electronics Supplier',
            'others' => ['Food Supplier', 'Textile Supplier'],
        ];
    }
}
