<?php

use App\Actions\ApprovalFlows\IndexApprovalFlowsAction;
use App\Domain\ApprovalFlows\ApprovalFlowFilterService;
use App\Http\Requests\ApprovalFlows\IndexApprovalFlowRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-flows');

test('execute calls filter service with correct parameters', function () {
    $filterService = Mockery::mock(ApprovalFlowFilterService::class);
    $action = new IndexApprovalFlowsAction($filterService);

    $request = Mockery::mock(IndexApprovalFlowRequest::class);
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('test');

    // Advanced filters
    $request->shouldReceive('get')->with('approvable_type')->andReturn('App\\Models\\AssetMovement');
    $request->shouldReceive('get')->with('is_active')->andReturn(1);

    // Sorting
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');

    // Expect calls to filter service
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type(Builder::class), 'test', ['name', 'code']);

    $filterService->shouldReceive('applyAdvancedFilters')
        ->once()
        ->with(Mockery::type(Builder::class), [
            'is_active' => 1,
        ]);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type(Builder::class), 'created_at', 'desc',
            ['id', 'name', 'code', 'approvable_type', 'is_active', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('getPaginationParams returns default values', function () {
    $filterService = Mockery::mock(ApprovalFlowFilterService::class);
    $action = new IndexApprovalFlowsAction($filterService);

    $request = Mockery::mock(IndexApprovalFlowRequest::class);
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
