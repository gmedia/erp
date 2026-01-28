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
    /**
     * Get the request class to test.
     * 
     * @return class-string
     */
    abstract protected function getRequestClass(): string;

    protected function createExportRequest(): object
    {
        $requestClass = $this->getRequestClass();
        return new $requestClass();
    }

    public function test_authorize_returns_true(): void
    {
        $request = $this->createExportRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_validation_rules(): void
    {
        $request = $this->createExportRequest();
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('sort_direction', $rules);
    }

    public function test_rules_validation_passes_with_valid_data(): void
    {
        $data = [
            'search' => 'test',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validation_passes_with_empty_data(): void
    {
        $data = [];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validation_fails_with_invalid_sort_by(): void
    {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_by'));
    }

    public function test_rules_validation_fails_with_invalid_sort_direction(): void
    {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, $this->createExportRequest()->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort_direction'));
    }

    public function test_rules_validation_passes_with_valid_sort_by_values(): void
    {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];
            $validator = validator($data, $this->createExportRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }

    public function test_rules_validation_passes_with_valid_sort_direction_values(): void
    {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];
            $validator = validator($data, $this->createExportRequest()->rules());
            $this->assertFalse($validator->fails());
        }
    }
}
