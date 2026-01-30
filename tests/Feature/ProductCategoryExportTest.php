<?php

namespace Tests\Feature;

use App\Exports\ProductCategoryExport;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class ProductCategoryExportTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return ProductCategoryExport::class;
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Finished Goods',
            'others' => ['Raw Materials', 'SaaS'],
        ];
    }

    public function test_headings_returns_correct_column_headers(): void
    {
        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);

        $headings = $export->headings();

        $this->assertEquals(['ID', 'Name', 'Description', 'Created At', 'Updated At'], $headings);
    }

    public function test_map_transforms_data_correctly_with_timestamps(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
            'description' => 'Test Description',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Test Item', $mapped[1]);
        $this->assertEquals('Test Description', $mapped[2]);
        $this->assertEquals('2023-01-15T14:30:00+00:00', $mapped[3]);
        $this->assertEquals('2023-01-20T09:15:00+00:00', $mapped[4]);
    }

    public function test_map_handles_null_timestamps_gracefully(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
            'description' => null,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Test Item', $mapped[1]);
        $this->assertNull($mapped[2]);
        $this->assertNull($mapped[3]);
        $this->assertNull($mapped[4]);
    }

    public function test_map_handles_carbon_timestamp_objects(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Carbon Test',
            'description' => 'Test Description'
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $item->created_at);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Carbon Test', $mapped[1]);
        $this->assertEquals('Test Description', $mapped[2]);
        $this->assertIsString($mapped[3]);
        $this->assertIsString($mapped[4]);
    }
}
