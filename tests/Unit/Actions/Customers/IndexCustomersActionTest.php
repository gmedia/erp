<?php

use App\Actions\Customers\IndexCustomersAction;
use App\Domain\Customers\CustomerFilterService;
use App\Http\Requests\Customers\IndexCustomerRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customers');

test('execute calls filter service with correct parameters', function () {
    $filterService = Mockery::mock(CustomerFilterService::class);
    $action = new IndexCustomersAction($filterService);

    $request = Mockery::mock(IndexCustomerRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('test');
    
    // Advanced filters
    $request->shouldReceive('get')->with('branch_id')->andReturn(1);
    $request->shouldReceive('get')->with('category_id')->andReturn(1);
    $request->shouldReceive('get')->with('status')->andReturn('active');

    // Sorting
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');

    // Expect calls to filter service
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'test', ['name', 'email', 'phone']);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), [
            'branch_id' => 1,
            'category_id' => 1,
            'status' => 'active',
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', 
            ['id', 'name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('getPaginationParams returns default values', function () {
    $filterService = Mockery::mock(CustomerFilterService::class);
    $action = new IndexCustomersAction($filterService);

    $request = Mockery::mock(IndexCustomerRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('getPaginationParams');

    $result = $method->invoke($action, $request);

    expect($result)->toBe([
        'perPage' => 15,
        'page' => 1,
    ]);
});
