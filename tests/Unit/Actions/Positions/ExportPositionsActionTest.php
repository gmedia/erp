<?php

use App\Actions\Positions\ExportPositionsAction;
use App\Domain\Positions\PositionFilterService;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('execute exports positions and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new PositionFilterService;
    $action = new ExportPositionsAction($filterService);

    Position::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('positions_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new PositionFilterService;
    $action = new ExportPositionsAction($filterService);

    Position::factory()->create(['name' => 'Developer']);
    Position::factory()->create(['name' => 'Manager']);

    // Mock request with search
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'dev']);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $filterService = new PositionFilterService;
    $action = new ExportPositionsAction($filterService);

    Position::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportPositionRequest::class);
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

    $filterService = new PositionFilterService;
    $action = new ExportPositionsAction($filterService);

    Position::factory()->count(2)->create();

    // Mock request with some null values
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
