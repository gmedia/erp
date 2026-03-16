<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Filter Service classes.
 *
 * Requires the consumer to define:
 * - getFilterServiceClass(): string - The filter service class to test
 * - getModelClass(): string - The model class for factory
 */
trait SimpleCrudFilterServiceTestTrait
{
    public function testApplySearchAddsWhereClauseForSearchTerm(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass;

        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Beta Item']);
        $modelClass::factory()->create(['name' => 'Gamma Item']);

        $query = $modelClass::query();
        $service->applySearch($query, 'alpha', ['name']);

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Alpha Item', $results->first()->name);
    }

    public function testApplySearchSearchesAcrossMultipleFields(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass;

        $modelClass::factory()->create(['name' => 'First Item']);
        $modelClass::factory()->create(['name' => 'Second Item']);

        $query = $modelClass::query();
        $service->applySearch($query, 'item', ['name']);

        $results = $query->get();

        $this->assertCount(2, $results);
    }

    public function testApplySortingAppliesAscendingSortWhenAllowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass;

        $modelClass::factory()->create(['name' => 'Zeta Item']);
        $modelClass::factory()->create(['name' => 'Alpha Item']);

        $query = $modelClass::query();
        $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

        $results = $query->get();

        $this->assertEquals('Alpha Item', $results->first()->name);
        $this->assertEquals('Zeta Item', $results->last()->name);
    }

    public function testApplySortingAppliesDescendingSortWhenAllowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass;

        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Zeta Item']);

        $query = $modelClass::query();
        $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

        $results = $query->get();

        $this->assertEquals('Zeta Item', $results->first()->name);
        $this->assertEquals('Alpha Item', $results->last()->name);
    }

    public function testApplySortingDoesNotApplySortWhenFieldNotAllowed(): void
    {
        $serviceClass = $this->getFilterServiceClass();
        $modelClass = $this->getModelClass();
        $service = new $serviceClass;

        $modelClass::factory()->create(['name' => 'Test Item']);

        $query = $modelClass::query();
        $originalSql = $query->toSql();

        $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

        $this->assertEquals($originalSql, $query->toSql());
    }

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
}
