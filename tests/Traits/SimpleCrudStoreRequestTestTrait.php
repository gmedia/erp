<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Store Request classes.
 *
 * Requires the consumer to define:
 * - getRequestClass(): string - The request class to test
 * - getModelClass(): string - The model class for unique validation
 */
trait SimpleCrudStoreRequestTestTrait
{
    public function testAuthorizeReturnsTrue(): void
    {
        $request = $this->createStoreRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesValidationPassesWithValidData(): void
    {
        $data = ['name' => 'Valid Name'];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationFailsWithMissingName(): void
    {
        $data = [];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function testRulesValidationFailsWithEmptyName(): void
    {
        $data = ['name' => ''];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function testRulesValidationFailsWithNameTooLong(): void
    {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function testRulesValidationPassesWithUniqueName(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->create(['name' => 'Existing Name']);

        $data = ['name' => 'Different Name'];

        $validator = validator($data, $this->createStoreRequest()->rules());

        $this->assertFalse($validator->fails());
    }

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

        return new $requestClass;
    }
}
