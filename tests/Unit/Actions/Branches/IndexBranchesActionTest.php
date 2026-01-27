<?php

use App\Actions\Branches\IndexBranchesAction;
use App\Domain\Branches\BranchFilterService;
use App\Http\Requests\Branches\IndexBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('branches');

test('execute returns paginated branches', function () {
    $filterService = new BranchFilterService;
    $action = new IndexBranchesAction($filterService);

    Branch::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(IndexBranchRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    $filterService = new BranchFilterService;
    $action = new IndexBranchesAction($filterService);

    Branch::factory()->create(['name' => 'Main Branch']);
    Branch::factory()->create(['name' => 'Side Branch']);

    // Mock request with search
    $request = Mockery::mock(IndexBranchRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    $request->shouldReceive('get')->with('search')->andReturn('main');
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Main Branch');
});

test('execute sorts results', function () {
    $filterService = new BranchFilterService;
    $action = new IndexBranchesAction($filterService);

    Branch::factory()->create(['name' => 'A Branch']);
    Branch::factory()->create(['name' => 'B Branch']);

    // Mock request with sort
    $request = Mockery::mock(IndexBranchRequest::class);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('name');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');
    $request->shouldReceive('get')->with('per_page', 15)->andReturn(15);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->first()->name)->toBe('B Branch');
});
