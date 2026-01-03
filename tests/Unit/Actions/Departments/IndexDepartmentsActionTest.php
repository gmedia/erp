<?php

use App\Actions\Departments\IndexDepartmentsAction;
use App\Domain\Departments\DepartmentFilterService;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

test('execute returns paginated departments', function () {
    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new IndexDepartmentsAction($filterService);

    // Create test departments
    Department::factory()->count(5)->create();

    // Mock request
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('execute applies search filter when provided', function () {
    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new IndexDepartmentsAction($filterService);

    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    // Mock request with search
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('eng');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'eng', ['name']);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('execute uses custom pagination parameters', function () {
    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new IndexDepartmentsAction($filterService);

    Department::factory()->count(10)->create();

    // Mock request with custom pagination
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(5);
    $request->shouldReceive('get')->with('page', 1)->andReturn(2);

    // Mock filter service calls
    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result->perPage())->toBe(5)
        ->and($result->currentPage())->toBe(2);
});
