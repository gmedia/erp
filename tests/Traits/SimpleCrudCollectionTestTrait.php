<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Collection classes.
 *
 * Requires the consumer to define:
 * - getCollectionClass(): string - The collection class to test
 * - getModelClass(): string - The model class for factory
 */
trait SimpleCrudCollectionTestTrait
{
    public function testCollectsPropertyIsSetCorrectly(): void
    {
        $collectionClass = $this->getCollectionClass();
        $collection = new $collectionClass([]);

        $this->assertEquals($this->getResourceClass(), $collection->collects);
    }

    public function testCollectionTransformsMultipleItemsCorrectly(): void
    {
        $modelClass = $this->getModelClass();
        $models = $modelClass::factory()->count(3)->create();

        $collectionClass = $this->getCollectionClass();
        $collection = new $collectionClass($models);
        $request = new \Illuminate\Http\Request;

        $result = $collection->toArray($request);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $index => $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
            $this->assertEquals($models[$index]->id, $item['id']);
            $this->assertEquals($models[$index]->name, $item['name']);
            $this->assertIsString($item['created_at']);
            $this->assertIsString($item['updated_at']);
        }
    }

    public function testCollectionReturnsEmptyArrayWhenNoItems(): void
    {
        $collectionClass = $this->getCollectionClass();
        $collection = new $collectionClass(collect());
        $request = new \Illuminate\Http\Request;

        $result = $collection->toArray($request);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Get the collection class to test.
     *
     * @return class-string
     */
    abstract protected function getCollectionClass(): string;

    /**
     * Get the model class for factory.
     *
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the resource class that the collection should collect.
     *
     * @return class-string
     */
    protected function getResourceClass(): string
    {
        return \App\Http\Resources\SimpleCrudResource::class;
    }
}
