<?php

namespace Tests\Traits;

use Mockery;

/**
 * Trait for testing Simple CRUD Export Action classes.
 *
 * Requires the consumer to define:
 * - getActionClass(): string - The action class to test
 * - getModelClass(): string - The model class for factory
 * - getRequestClass(): string - The request class to mock
 * - getExpectedFilenamePrefix(): string - Expected filename prefix
 */
trait SimpleCrudExportActionTestTrait
{
    public function testExecuteExportsAndReturnsFileInfo(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(3)->create();

        $actionClass = $this->getActionClass();
        $action = new $actionClass;

        $request = $this->createMockExportRequest(['validated' => []]);
        $response = $action->execute($request);

        $data = $response->getData(true);

        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('filename', $data);
        $this->assertStringContainsString($this->getExpectedFilenamePrefix(), $data['filename']);
        $this->assertStringEndsWith('.xlsx', $data['filename']);
    }

    public function testExecuteExportsWithSearchFilter(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->create(['name' => 'Match Item']);
        $modelClass::factory()->create(['name' => 'Other Item']);

        $actionClass = $this->getActionClass();
        $action = new $actionClass;

        $request = $this->createMockExportRequest([
            'validated' => ['search' => 'Match'],
            'filled' => ['search'],
        ]);

        $response = $action->execute($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('filename', $data);
    }

    public function testExecuteExportsWithCustomSortParameters(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(2)->create();

        $actionClass = $this->getActionClass();
        $action = new $actionClass;

        $request = $this->createMockExportRequest([
            'validated' => [
                'sort_by' => 'name',
                'sort_direction' => 'asc',
            ],
        ]);

        $response = $action->execute($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('filename', $data);
    }

    public function testExecuteHandlesEmptyFilters(): void
    {
        $modelClass = $this->getModelClass();
        $modelClass::factory()->count(2)->create();

        $actionClass = $this->getActionClass();
        $action = new $actionClass;

        $request = $this->createMockExportRequest(['validated' => []]);
        $response = $action->execute($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

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

    /**
     * Get expected filename prefix.
     */
    abstract protected function getExpectedFilenamePrefix(): string;

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

    protected function createMockExportRequest(array $options = []): object
    {
        $request = Mockery::mock($this->getRequestClass());

        $validated = $options['validated'] ?? [];
        $request->shouldReceive('validated')->andReturn($validated);

        $filled = $options['filled'] ?? [];
        foreach (['search'] as $field) {
            $request->shouldReceive('filled')
                ->with($field)
                ->andReturn(in_array($field, $filled));
        }

        return $request;
    }
}
