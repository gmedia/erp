<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Resource classes.
 * 
 * Requires the consumer to define:
 * - getResourceClass(): string - The resource class to test
 * - getModelClass(): string - The model class for factory
 */
trait SimpleCrudResourceTestTrait
{
    /**
     * Get the resource class to test.
     * 
     * @return class-string
     */
    abstract protected function getResourceClass(): string;

    /**
     * Get the model class for factory.
     * 
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    public function test_to_array_transforms_model_correctly(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create([
            'name' => 'Test Item',
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-02 11:00:00',
        ]);

        $resourceClass = $this->getResourceClass();
        $resource = new $resourceClass($model);
        $request = new \Illuminate\Http\Request();

        $result = $resource->toArray($request);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($model->id, $result['id']);
        $this->assertEquals('Test Item', $result['name']);
        $this->assertIsString($result['created_at']);
        $this->assertIsString($result['updated_at']);
    }

    public function test_to_array_includes_all_required_fields(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();

        $resourceClass = $this->getResourceClass();
        $resource = new $resourceClass($model);
        $request = new \Illuminate\Http\Request();

        $result = $resource->toArray($request);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
        $this->assertEquals($model->id, $result['id']);
        $this->assertEquals($model->name, $result['name']);
    }

    public function test_to_array_handles_null_timestamps(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $model->created_at = null;
        $model->updated_at = null;

        $resourceClass = $this->getResourceClass();
        $resource = new $resourceClass($model);
        $request = new \Illuminate\Http\Request();

        $result = $resource->toArray($request);

        $this->assertNull($result['created_at']);
        $this->assertNull($result['updated_at']);
    }
}
