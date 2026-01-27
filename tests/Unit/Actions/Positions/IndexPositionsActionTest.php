<?php

use App\Actions\Positions\IndexPositionsAction;
use App\Domain\Positions\PositionFilterService;
use App\Http\Requests\Positions\IndexPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('positions');

test('execute returns paginated positions', function () {
    $filterService = new PositionFilterService;
    $action = new IndexPositionsAction($filterService);

    Position::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(IndexPositionRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    $filterService = new PositionFilterService;
    $action = new IndexPositionsAction($filterService);

    Position::factory()->create(['name' => 'Manager']);
    Position::factory()->create(['name' => 'Staff']);

    // Mock request with search
    $request = Mockery::mock(IndexPositionRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('manager');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Manager');
});

test('execute sorts results', function () {
    $filterService = new PositionFilterService;
    $action = new IndexPositionsAction($filterService);

    Position::factory()->create(['name' => 'A Position']);
    Position::factory()->create(['name' => 'B Position']);

    // Mock request with sort
    $request = Mockery::mock(IndexPositionRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('name');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->first()->name)->toBe('B Position');
});
