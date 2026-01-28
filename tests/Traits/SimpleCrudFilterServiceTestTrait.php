<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Trait for testing Simple CRUD Filter Service classes.
 * 
 * Requires the consumer to define:
 * - getFilterServiceClass(): string - The filter service class to test
 * - getModelClass(): string - The model class for factory
 */
trait SimpleCrudFilterServiceTestTrait
{
    /**
     * Get the filter service class to test.
     * 
     * @return class-string
     */
    abstract protected function getFilterServiceClass(): string;

    /**
     * Get the model class for factory.
     * 
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getModelClass()::query()->delete();
    }

    public function test_apply_search_adds_where_clause_for_search_term(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass();

        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Beta Item']);
        $modelClass::factory()->create(['name' => 'Gamma Item']);

        $query = $modelClass::query();
        $service->applySearch($query, 'alpha', ['name']);

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Alpha Item', $results->first()->name);
    }

    public function test_apply_search_searches_across_multiple_fields(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass();

        $modelClass::factory()->create(['name' => 'First Item']);
        $modelClass::factory()->create(['name' => 'Second Item']);

        $query = $modelClass::query();
        $service->applySearch($query, 'item', ['name']);

        $results = $query->get();

        $this->assertCount(2, $results);
    }

    public function test_apply_sorting_applies_ascending_sort_when_allowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass();

        $modelClass::factory()->create(['name' => 'Zeta Item']);
        $modelClass::factory()->create(['name' => 'Alpha Item']);

        $query = $modelClass::query();
        $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

        $results = $query->get();

        $this->assertEquals('Alpha Item', $results->first()->name);
        $this->assertEquals('Zeta Item', $results->last()->name);
    }

    public function test_apply_sorting_applies_descending_sort_when_allowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass();

        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Zeta Item']);

        $query = $modelClass::query();
        $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

        $results = $query->get();

        $this->assertEquals('Zeta Item', $results->first()->name);
        $this->assertEquals('Alpha Item', $results->last()->name);
    }

    public function test_apply_sorting_does_not_apply_sort_when_field_not_allowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass();

        $modelClass::factory()->create(['name' => 'Test Item']);

        $query = $modelClass::query();
        $originalSql = $query->toSql();

        $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

        $this->assertEquals($originalSql, $query->toSql());
    }
}
