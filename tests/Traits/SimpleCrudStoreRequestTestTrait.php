<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Trait for testing Simple CRUD Store Request classes.
 * 
 * Requires the consumer to define:
 * - getRequestClass(): string - The request class to test
 * - getModelClass(): string - The model class for unique validation
 */
trait SimpleCrudStoreRequestTestTrait
{
    /**
     * Get the request class to test.
     * 
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    /**
     * Get the model class for factory.
     * 
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    protected function createStoreRequest(): object
    {
        $requestClass = $this->getRequestClass();
        return new $requestClass();
    }

    public function test_authorize_returns_true(): void
    {
        $request = $this->createStoreRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_rules_validation_passes_with_valid_data(): void
    {
        $data = ['name' => 'Valid Name'];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validation_fails_with_missing_name(): void
    {
        $data = [];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_rules_validation_fails_with_empty_name(): void
    {
        $data = ['name' => ''];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_rules_validation_fails_with_name_too_long(): void
    {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_rules_validation_passes_with_unique_name(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->create(['name' => 'Existing Name']);

        $data = ['name' => 'Different Name'];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertFalse($validator->fails());
    }
}
