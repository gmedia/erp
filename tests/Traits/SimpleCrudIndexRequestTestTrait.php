<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Index Request classes.
 * 
 * Requires the consumer to define:
 * - getRequestClass(): string - The request class to test
 */
trait SimpleCrudIndexRequestTestTrait
{
    /**
     * Get the request class to test.
     * 
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    protected function createRequest(): object
    {
        $requestClass = $this->getRequestClass();
        return new $requestClass();
    }

    public function test_authorize_returns_true(): void
    {
        $request = $this->createRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_validation_rules(): void
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('sort_direction', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('page', $rules);
    }

    public function test_rules_validation_passes_with_valid_data(): void
    {
        $data = [
            'search' => 'test search',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'page' => 1,
        ];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validation_passes_with_empty_data(): void
    {
        $data = [];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validation_fails_with_invalid_sort_by(): void
    {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_by'));
    }

    public function test_rules_validation_fails_with_invalid_sort_direction(): void
    {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_direction'));
    }

    public function test_rules_validation_fails_with_per_page_too_small(): void
    {
        $data = ['per_page' => 0];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));
    }

    public function test_rules_validation_fails_with_per_page_too_large(): void
    {
        $data = ['per_page' => 101];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));
    }

    public function test_rules_validation_fails_with_page_less_than_1(): void
    {
        $data = ['page' => 0];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('page'));
    }

    public function test_rules_validation_passes_with_valid_sort_by_values(): void
    {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];
            $validator = validator($data, $this->createRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    public function test_rules_validation_passes_with_valid_sort_direction_values(): void
    {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];
            $validator = validator($data, $this->createRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }
}
