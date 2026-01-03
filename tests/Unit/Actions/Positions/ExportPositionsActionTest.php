<?php

use App\Actions\Positions\ExportPositionsAction;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('execute exports positions and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportPositionsAction();

    Position::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

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

    $action = new ExportPositionsAction();

    Position::factory()->create(['name' => 'Developer']);
    Position::factory()->create(['name' => 'Manager']);

    // Mock request with search
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'dev']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute exports with custom sort parameters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportPositionsAction();

    Position::factory()->count(2)->create();

    // Mock request with custom sort
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'name',
        'sort_direction' => 'asc'
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});

test('execute filters out null values from filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportPositionsAction();

    Position::factory()->count(2)->create();

    // Mock request with some null values
    $request = Mockery::mock(ExportPositionRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
        'sort_by' => null,
        'sort_direction' => null,
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
