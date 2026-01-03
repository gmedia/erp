<?php

use App\Actions\Departments\ExportDepartmentsAction;
use App\Domain\Departments\DepartmentFilterService;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;

uses(RefreshDatabase::class);

test('execute exports departments and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    // Mock filter service calls
    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('departments_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute applies search filter when provided', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    // Mock request with search
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'eng']);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    // Mock filter service calls
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'eng', ['name']);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute uses custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = Mockery::mock(DepartmentFilterService::class);
    $action = new ExportDepartmentsAction($filterService);

    Department::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'name',
        'sort_direction' => 'asc'
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    // Mock filter service calls
    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
