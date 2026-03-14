<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Update Request classes.
 *
 * Requires the consumer to define:
 * - getRequestClass(): string - The request class to test
 * - getModelClass(): string - The model class for factory
 * - getRouteParameterName(): string - Route parameter name (e.g., 'department')
 */
trait SimpleCrudUpdateRequestTestTrait
{
    public function testAuthorizeReturnsTrue(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $request = $this->createUpdateRequest($model);

        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsValidationRules(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $request = $this->createUpdateRequest($model);

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('sometimes', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function testRulesValidationPassesWithValidData(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $data = ['name' => 'Updated Name'];

        $request = $this->createUpdateRequest($model);
        $validator = validator($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationPassesWithoutNameField(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $data = [];

        $request = $this->createUpdateRequest($model);
        $validator = validator($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationFailsWithEmptyName(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create();
        $data = ['name' => ''];

        $request = $this->createUpdateRequest($model);
        $validator = validator($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function testRulesValidationAllowsSameNameForCurrentModel(): void
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::factory()->create(['name' => 'Current Name']);
        $data = ['name' => 'Current Name'];

        $request = $this->createUpdateRequest($model);
        $validator = validator($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationFailsWithDuplicateNameFromAnotherModel(): void
    {
        $modelClass = $this->getModelClass();
        $existingModel = $modelClass::factory()->create(['name' => 'Existing Name']);
        $model = $modelClass::factory()->create(['name' => 'Current Name']);
        $data = ['name' => 'Existing Name'];

        $request = $this->createUpdateRequest($model);
        $validator = validator($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
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

    /**
     * Get the route parameter name.
     */
    abstract protected function getRouteParameterName(): string;

    protected function createUpdateRequest($model): object
    {
        $requestClass = $this->getRequestClass();
        $request = new $requestClass;
        $request->setRouteResolver(function () use ($model) {
            return new class($model)
            {
                private $model;

                public function __construct($model)
                {
                    $this->model = $model;
                }

                public function parameter($name)
                {
                    return $this->model;
                }
            };
        });

        return $request;
    }
}
