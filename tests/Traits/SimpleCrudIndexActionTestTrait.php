<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;

/**
 * Trait for testing Simple CRUD Index Action classes.
 * 
 * Requires the consumer to define:
 * - getActionClass(): string - The action class to test
 * - getModelClass(): string - The model class for factory
 * - getRequestClass(): string - The request class to mock
 */
trait SimpleCrudIndexActionTestTrait
{
    /**
     * Get the action class to test.
     * 
     * @return class-string
     */
    abstract protected function getActionClass(): string;

    /**
     * Get the model class for factory.
     * 
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the request class to mock.
     * 
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getModelClass()::query()->delete();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMockRequest(array $options = []): object
    {
        $request = Mockery::mock($this->getRequestClass());
        
        $filled = $options['filled'] ?? [];
        foreach (['search'] as $field) {
            $request->shouldReceive('filled')
                ->with($field)
                ->andReturn(in_array($field, $filled));
        }
        
        if (in_array('search', $filled)) {
            $request->shouldReceive('get')
                ->with('search')
                ->andReturn($options['search'] ?? '');
        }
        
        $request->shouldReceive('get')
            ->with('sort_by', 'created_at')
            ->andReturn($options['sort_by'] ?? 'created_at');
        
        $request->shouldReceive('get')
            ->with('sort_direction', 'desc')
            ->andReturn($options['sort_direction'] ?? 'desc');
        
        $request->shouldReceive('get')
            ->with('per_page', 15)
            ->andReturn($options['per_page'] ?? 15);
        
        return $request;
    }

    public function test_execute_returns_paginated_results(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(3)->create();

        $actionClass = $this->getActionClass();
        $action = new $actionClass();
        
        $request = $this->createMockRequest();
        $result = $action->execute($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->count());
    }

    public function test_execute_filters_by_search_term(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->create(['name' => 'Alpha Item']);
        $modelClass::factory()->create(['name' => 'Beta Item']);

        $actionClass = $this->getActionClass();
        $action = new $actionClass();
        
        $request = $this->createMockRequest([
            'filled' => ['search'],
            'search' => 'alpha',
        ]);
        
        $result = $action->execute($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertEquals('Alpha Item', $result->first()->name);
    }

    public function test_execute_sorts_results(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->create(['name' => 'A Item']);
        $modelClass::factory()->create(['name' => 'B Item']);

        $actionClass = $this->getActionClass();
        $action = new $actionClass();
        
        $request = $this->createMockRequest([
            'sort_by' => 'name',
            'sort_direction' => 'desc',
        ]);
        
        $result = $action->execute($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals('B Item', $result->first()->name);
    }
}
