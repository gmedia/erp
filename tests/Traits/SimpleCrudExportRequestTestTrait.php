<?php

namespace Tests\Traits;

/**
 * Trait for testing Simple CRUD Export Request classes.
 *
 * Requires the consumer to define:
 * - getRequestClass(): string - The request class to test
 */
trait SimpleCrudExportRequestTestTrait
{
    public function testAuthorizeReturnsTrue(): void
    {
        $request = $this->createExportRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsValidationRules(): void
    {
        $request = $this->createExportRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('sort_direction', $rules);
    }

    public function testRulesValidationPassesWithValidData(): void
    {
        $data = [
            'search' => 'test',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationPassesWithEmptyData(): void
    {
        $data = [];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function testRulesValidationFailsWithInvalidSortBy(): void
    {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_by'));
    }

    public function testRulesValidationFailsWithInvalidSortDirection(): void
    {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_direction'));
    }

    public function testRulesValidationPassesWithValidSortByValues(): void
    {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];
            $validator = validator($data, $this->createExportRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    public function testRulesValidationPassesWithValidSortDirectionValues(): void
    {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];
            $validator = validator($data, $this->createExportRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    /**
     * Get the request class to test.
     *
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    protected function createExportRequest(): object
    {
        $requestClass = $this->getRequestClass();

        return new $requestClass;
    }
}
