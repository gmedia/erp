<?php

use App\Actions\Departments\IndexDepartmentsAction;
use App\Domain\Departments\DepartmentFilterService;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('departments');

test('execute returns paginated departments', function () {
    $filterService = new DepartmentFilterService;
    $action = new IndexDepartmentsAction($filterService);

    Department::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    $filterService = new DepartmentFilterService;
    $action = new IndexDepartmentsAction($filterService);

    Department::factory()->create(['name' => 'IT Dept']);
    Department::factory()->create(['name' => 'HR Dept']);

    // Mock request with search
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('it');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('IT Dept');
});

test('execute sorts results', function () {
    $filterService = new DepartmentFilterService;
    $action = new IndexDepartmentsAction($filterService);

    Department::factory()->create(['name' => 'A Dept']);
    Department::factory()->create(['name' => 'B Dept']);

    // Mock request with sort
    $request = Mockery::mock(IndexDepartmentRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('name');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->first()->name)->toBe('B Dept');
});
