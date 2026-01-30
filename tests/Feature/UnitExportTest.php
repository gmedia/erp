<?php

namespace Tests\Feature;

use App\Exports\UnitExport;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class UnitExportTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return UnitExport::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Kilogram',
            'others' => ['Meter', 'Liter'],
        ];
    }

    public function test_headings_returns_correct_column_headers(): void
    {
        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);

        $headings = $export->headings();

        $this->assertEquals(['ID', 'Name', 'Symbol', 'Created At', 'Updated At'], $headings);
    }

    public function test_map_transforms_data_correctly_with_timestamps(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
            'symbol' => 'kg',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Test Item', $mapped[1]);
        $this->assertEquals('kg', $mapped[2]);
        $this->assertEquals('2023-01-15T14:30:00+00:00', $mapped[3]);
        $this->assertEquals('2023-01-20T09:15:00+00:00', $mapped[4]);
    }

    public function test_map_handles_null_timestamps_gracefully(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
            'symbol' => null,
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
}
