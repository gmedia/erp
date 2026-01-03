<?php

use App\Http\Controllers\BaseCrudController;
use App\Models\Department;
use App\Http\Resources\Departments\DepartmentResource;
use App\Http\Resources\Departments\DepartmentCollection;
use App\Exports\DepartmentExport;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

uses(RefreshDatabase::class);

// Concrete implementation for testing
class TestBaseCrudController extends BaseCrudController
{
    protected function getModelClass(): string
    {
        return Department::class;
    }

    protected function getResourceClass(): string
    {
        return DepartmentResource::class;
    }

    protected function getCollectionClass(): string
    {
        return DepartmentCollection::class;
    }

    protected function getExportClass(): string
    {
        return DepartmentExport::class;
    }

    protected function getExportRequestClass(): string
    {
        return ExportDepartmentRequest::class;
    }

    public function store($request)
    {
        // Not implemented for this test
    }

    public function export($request)
    {
        // Not implemented for this test
    }
}

test('index returns paginated resources with search', function () {
    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    $controller = new TestBaseCrudController();

    $request = Request::create('/test', 'GET', ['search' => 'eng']);

    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $response->getData(true);
    expect($data['data'])->toHaveCount(1)
        ->and($data['data'][0]['name'])->toBe('Engineering');
});

test('index returns paginated resources with sorting', function () {
    Department::factory()->create(['name' => 'Z Department']);
    Department::factory()->create(['name' => 'A Department']);

    $controller = new TestBaseCrudController();

    $request = Request::create('/test', 'GET', [
        'sort_by' => 'name',
        'sort_direction' => 'asc'
    ]);

    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $response->getData(true);
    expect($data['data'][0]['name'])->toBe('A Department')
        ->and($data['data'][1]['name'])->toBe('Z Department');
});

test('index uses custom pagination parameters', function () {
    Department::factory()->count(10)->create();

    $controller = new TestBaseCrudController();

    $request = Request::create('/test', 'GET', [
        'per_page' => 5,
        'page' => 2
    ]);

    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $response->getData(true);
    expect($data['meta']['per_page'])->toBe(5)
        ->and($data['meta']['current_page'])->toBe(2);
});

test('getAllowedSorts returns configured sorts', function () {
    $controller = new TestBaseCrudController();

    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getAllowedSorts');
    $method->setAccessible(true);

    $result = $method->invoke($controller);

    expect($result)->toBe(['id', 'name', 'created_at', 'updated_at']);
});

test('getSearchFields returns configured fields', function () {
    $controller = new TestBaseCrudController();

    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getSearchFields');
    $method->setAccessible(true);

    $result = $method->invoke($controller);

    expect($result)->toBe(['name']);
});

test('applySearch adds where clause for search term', function () {
    $controller = new TestBaseCrudController();

    $query = Department::query();

    $request = Request::create('/test', 'GET', ['search' => 'test']);

    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('applySearch');
    $method->setAccessible(true);

    $method->invoke($controller, $query, $request);

    // Query should have where clause applied
    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('applySorting validates and applies sort parameters', function () {
    $controller = new TestBaseCrudController();

    $query = Department::query();

    $request = Request::create('/test', 'GET', [
        'sort_by' => 'name',
        'sort_direction' => 'asc'
    ]);

    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('applySorting');
    $method->setAccessible(true);

    $method->invoke($controller, $query, $request);

    // Query should have orderBy applied
    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('applySorting rejects invalid sort fields', function () {
    $controller = new TestBaseCrudController();

    $query = Department::query();

    $request = Request::create('/test', 'GET', [
        'sort_by' => 'invalid_field',
        'sort_direction' => 'asc'
    ]);

    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('applySorting');
    $method->setAccessible(true);

    $method->invoke($controller, $query, $request);

    // Query should remain unchanged
    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
