<?php

use App\Actions\Departments\ExportDepartmentsAction;
use App\Domain\Departments\DepartmentFilterService;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('execute exports departments and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new DepartmentFilterService;
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('departments_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new DepartmentFilterService;
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    // Mock request with search
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'eng']);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new DepartmentFilterService;
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute filters out null values from filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new DepartmentFilterService;
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->count(2)->create();

    // Mock request with some null values
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
