<?php

use App\Actions\Positions\IndexPositionsAction;
use App\Domain\Positions\PositionFilterService;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('execute returns paginated positions', function () {
    $filterService = Mockery::mock(PositionFilterService::class);
    $action = new IndexPositionsAction($filterService);

    // Create test positions
    Position::factory()->count(5)->create();

    // Mock request
    $request = Mockery::mock(IndexPositionRequest::class);
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
    $filterService = Mockery::mock(PositionFilterService::class);
    $action = new IndexPositionsAction($filterService);

    Position::factory()->create(['name' => 'Developer']);
    Position::factory()->create(['name' => 'Manager']);

    // Mock request with search
    $request = Mockery::mock(IndexPositionRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('dev');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);
    $request->shouldReceive('get')->with('page', 1)->andReturn(1);

    // Mock filter service calls
    $filterService->shouldReceive('applySearch')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'dev', ['name']);

    $filterService->shouldReceive('applySorting')
        ->once()
        ->with(Mockery::type('Illuminate\Database\Eloquent\Builder'), 'created_at', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

test('execute uses custom pagination parameters', function () {
    $filterService = Mockery::mock(PositionFilterService::class);
    $action = new IndexPositionsAction($filterService);

    Position::factory()->count(10)->create();

    // Mock request with custom pagination
    $request = Mockery::mock(IndexPositionRequest::class);
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

test('getPaginationParams returns default values', function () {
    $filterService = Mockery::mock(PositionFilterService::class);
    $action = new IndexPositionsAction($filterService);

    $request = Mockery::mock(IndexPositionRequest::class);
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
