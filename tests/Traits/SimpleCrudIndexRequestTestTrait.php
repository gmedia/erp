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
    public function testAuthorizeReturnsTrue(): void
    {
        $request = $this->createRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsValidationRules(): void
    {
        $request = $this->createRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('sort_direction', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('page', $rules);
    }

    public function testRulesValidationPassesWithValidData(): void
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

    public function testRulesValidationPassesWithEmptyData(): void
    {
        $data = [];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationFailsWithInvalidSortBy(): void
    {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_by'));
    }

    public function testRulesValidationFailsWithInvalidSortDirection(): void
    {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_direction'));
    }

    public function testRulesValidationFailsWithPerPageTooSmall(): void
    {
        $data = ['per_page' => 0];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));
    }

    public function testRulesValidationFailsWithPerPageTooLarge(): void
    {
        $data = ['per_page' => 101];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));
    }

    public function testRulesValidationFailsWithPageLessThan1(): void
    {
        $data = ['page' => 0];

        $validator = validator($data, $this->createRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('page'));
    }

    public function testRulesValidationPassesWithValidSortByValues(): void
    {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];
            $validator = validator($data, $this->createRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    public function testRulesValidationPassesWithValidSortDirectionValues(): void
    {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];
            $validator = validator($data, $this->createRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    /**
     * Get the request class to test.
     *
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    protected function createRequest(): object
    {
        $requestClass = $this->getRequestClass();

        return new $requestClass;
    }
}
