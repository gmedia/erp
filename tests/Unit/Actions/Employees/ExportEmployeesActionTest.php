<?php

use App\Actions\Employees\ExportEmployeesAction;
use App\Http\Requests\Employees\ExportEmployeeRequest;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('employees');

test('execute exports employees and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportEmployeesAction;

    Employee::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportEmployeeRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('employees_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportEmployeesAction;

    Employee::factory()->create(['name' => 'John Doe']);
    Employee::factory()->create(['name' => 'Jane Smith']);

    // Mock request with search
    $request = Mockery::mock(ExportEmployeeRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'john']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with department and position filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportEmployeesAction;

    Employee::factory()->count(2)->create();

    // Mock request with filters
    $request = Mockery::mock(ExportEmployeeRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'department_id' => 1,
        'position_id' => 1,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportEmployeesAction;

    Employee::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportEmployeeRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'salary',
        'sort_direction' => 'desc',
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute filters out null values from filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportEmployeesAction;

    Employee::factory()->count(2)->create();

    // Mock request with some null values
    $request = Mockery::mock(ExportEmployeeRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
        'department_id' => null,
        'position_id' => 1,
        'sort_by' => null,
        'sort_direction' => null,
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
