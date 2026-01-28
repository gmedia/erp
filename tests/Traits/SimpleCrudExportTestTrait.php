<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Trait for testing Simple CRUD Export classes.
 * 
 * Requires the consumer to define:
 * - getExportClass(): string - The export class to test
 * - getModelClass(): string - The model class for factory
 * - getSampleSearchTerm(): string - Search term that matches sample data
 * - getSampleNames(): array - Array of sample names for testing
 */
trait SimpleCrudExportTestTrait
{
    /**
     * Get the export class to test.
     * 
     * @return class-string
     */
    abstract protected function getExportClass(): string;

    /**
     * Get the model class for factory.
     * 
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    /**
     * Get sample names for testing.
     * 
     * @return array{match: string, others: array<string>}
     */
    abstract protected function getSampleData(): array;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getModelClass()::query()->delete();
    }

    public function test_query_applies_search_filter_case_insensitively(): void
    {
        $data = $this->getSampleData();
        $modelClass = $this->getModelClass();
        
        $modelClass::factory()->create(['name' => $data['match']]);
        foreach ($data['others'] as $name) {
            $modelClass::factory()->create(['name' => $name]);
        }

        $exportClass = $this->getExportClass();
        $export = new $exportClass(['search' => strtoupper(substr($data['match'], 0, 3))]);

        $results = $export->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($data['match'], $results->first()->name);
    }

    public function test_query_applies_exact_name_filter(): void
    {
        $data = $this->getSampleData();
        $modelClass = $this->getModelClass();
        
        $modelClass::factory()->create(['name' => $data['match']]);
        foreach ($data['others'] as $name) {
            $modelClass::factory()->create(['name' => $name]);
        }

        $exportClass = $this->getExportClass();
        $export = new $exportClass(['name' => $data['match']]);

        $results = $export->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($data['match'], $results->first()->name);
    }

    public function test_query_applies_ascending_sort_by_name(): void
    {
        $modelClass = $this->getModelClass();
        
        $modelClass::factory()->create(['name' => 'Zeta Item']);
        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Beta Item']);

        $exportClass = $this->getExportClass();
        $export = new $exportClass(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        $this->assertEquals('Alpha Item', $results[0]->name);
        $this->assertEquals('Beta Item', $results[1]->name);
        $this->assertEquals('Zeta Item', $results[2]->name);
    }

    public function test_query_applies_descending_sort_by_created_at_by_default(): void
    {
        $modelClass = $this->getModelClass();
        
        $first = $modelClass::factory()->create(['created_at' => now()->subDays(2)]);
        $second = $modelClass::factory()->create(['created_at' => now()->subDay()]);
        $third = $modelClass::factory()->create(['created_at' => now()]);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);

        $results = $export->query()->get();

        $this->assertEquals($third->id, $results[0]->id);
        $this->assertEquals($second->id, $results[1]->id);
        $this->assertEquals($first->id, $results[2]->id);
    }

    public function test_query_does_not_allow_invalid_sort_columns(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(2)->create();

        $exportClass = $this->getExportClass();
        $export = new $exportClass(['sort_by' => 'invalid_column', 'sort_direction' => 'asc']);

        // Should not throw error, just ignore invalid sort
        $results = $export->query()->get();

        $this->assertCount(2, $results);
    }

    public function test_query_combines_search_and_sorting(): void
    {
        $modelClass = $this->getModelClass();
        
        $modelClass::factory()->create(['name' => 'Alpha Test']);
        $modelClass::factory()->create(['name' => 'Beta Test']);
        $modelClass::factory()->create(['name' => 'Gamma Other']);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([
            'search' => 'Test',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
        ]);

        $results = $export->query()->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Beta Test', $results[0]->name);
        $this->assertEquals('Alpha Test', $results[1]->name);
    }

    public function test_headings_returns_correct_column_headers(): void
    {
        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);

        $headings = $export->headings();

        $this->assertEquals(['ID', 'Name', 'Created At', 'Updated At'], $headings);
    }

    public function test_map_transforms_data_correctly_with_timestamps(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Test Item', $mapped[1]);
        $this->assertEquals('2023-01-15T14:30:00+00:00', $mapped[2]);
        $this->assertEquals('2023-01-20T09:15:00+00:00', $mapped[3]);
    }

    public function test_map_handles_null_timestamps_gracefully(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create([
            'name' => 'Test Item',
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
    }

    public function test_map_handles_carbon_timestamp_objects(): void
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::factory()->create(['name' => 'Carbon Test']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $item->created_at);

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);
        $mapped = $export->map($item);

        $this->assertEquals($item->id, $mapped[0]);
        $this->assertEquals('Carbon Test', $mapped[1]);
        $this->assertIsString($mapped[2]);
        $this->assertIsString($mapped[3]);
    }

    public function test_handles_empty_filters_gracefully(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(3)->create();

        $exportClass = $this->getExportClass();
        $export = new $exportClass([]);

        $results = $export->query()->get();

        $this->assertCount(3, $results);
    }
}
