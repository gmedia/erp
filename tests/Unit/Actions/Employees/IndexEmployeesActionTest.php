<?php

use App\Actions\Employees\IndexEmployeesAction;
use App\Domain\Employees\EmployeeFilterService;
use App\Http\Requests\Employees\IndexEmployeeRequest;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('execute returns paginated employees without filters', function () {
    $filterService = Mockery::mock(EmployeeFilterService::class);
    $action = new IndexEmployeesAction($filterService);

    Employee::factory()->count(5)->create();

    // Mock request
    $request = Mockery::mock(IndexEmployeeRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('department')->andReturn(null);
    $request->shouldReceive('get')->with('position')->andReturn(null);
    $request->shouldReceive('get')->with('salary_min')->andReturn(null);
    $request->shouldReceive('get')->with('salary_max')->andReturn(null);
    $request->shouldReceive('get')->with('hire_date_from')->andReturn(null);
    $request->shouldReceive('get')->with('hire_date_to')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applyAdvancedFilters')
        ->twice()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), Mockery::type('array'));

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'name', 'email', 'phone', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('execute applies search filter when provided', function () {
    $filterService = Mockery::mock(EmployeeFilterService::class);
    $action = new IndexEmployeesAction($filterService);

    Employee::factory()->create(['name' => 'John Doe']);
    Employee::factory()->create(['name' => 'Jane Smith']);

    // Mock request with search
    $request = Mockery::mock(IndexEmployeeRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('john');
    $request->shouldReceive('get')->with('salary_min')->andReturn(null);
    $request->shouldReceive('get')->with('salary_max')->andReturn(null);
    $request->shouldReceive('get')->with('hire_date_from')->andReturn(null);
    $request->shouldReceive('get')->with('hire_date_to')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'john',
            ['name', 'email', 'phone', 'department', 'position']);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'salary_min' => null,
            'salary_max' => null,
            'hire_date_from' => null,
            'hire_date_to' => null,
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'name', 'email', 'phone', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('execute applies advanced filters when no search provided', function () {
    $filterService = Mockery::mock(EmployeeFilterService::class);
    $action = new IndexEmployeesAction($filterService);

    Employee::factory()->count(3)->create();

    // Mock request with filters but no search
    $request = Mockery::mock(IndexEmployeeRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('department')->andReturn('Engineering');
    $request->shouldReceive('get')->with('position')->andReturn('Developer');
    $request->shouldReceive('get')->with('salary_min')->andReturn(50000);
    $request->shouldReceive('get')->with('salary_max')->andReturn(80000);
    $request->shouldReceive('get')->with('hire_date_from')->andReturn('2023-01-01');
    $request->shouldReceive('get')->with('hire_date_to')->andReturn('2023-12-31');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls - should be called twice for different filter groups
    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'department' => 'Engineering',
            'position' => 'Developer',
        ]);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'salary_min' => 50000,
            'salary_max' => 80000,
            'hire_date_from' => '2023-01-01',
            'hire_date_to' => '2023-12-31',
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc',
            ['id', 'name', 'email', 'phone', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('getPaginationParams returns default values', function () {
    $filterService = Mockery::mock(EmployeeFilterService::class);
    $action = new IndexEmployeesAction($filterService);

    $request = Mockery::mock(IndexEmployeeRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('getPaginationParams');
    $method->setAccessible(true);

    $result = $method->invoke($action, $request);

    expect($result)->toBe([
        'perPage' => 15,
        'page' => 1,
    ]);
});

test('getPaginationParams returns custom values', function () {
    $filterService = Mockery::mock(EmployeeFilterService::class);
    $action = new IndexEmployeesAction($filterService);

    $request = Mockery::mock(IndexEmployeeRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(50);
    $request->shouldReceive('get')->with('page', 1)->andReturn(3);

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('getPaginationParams');
    $method->setAccessible(true);

    $result = $method->invoke($action, $request);

    expect($result)->toBe([
        'perPage' => 50,
        'page' => 3,
    ]);
});
