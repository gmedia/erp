<?php

use App\Actions\Warehouses\ExportWarehousesAction;
use App\Http\Requests\Warehouses\ExportWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('warehouses');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');

    Warehouse::factory()->count(3)->create();

    $action = new ExportWarehousesAction;
    $request = Mockery::mock(ExportWarehouseRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'branch_id' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $result = $action->execute($request);

    $filename = 'warehouses_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);

    Excel::assertStored('exports/' . $filename, 'public');
});

test('execute filters export by search term', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');

    Warehouse::factory()->create(['name' => 'Main Warehouse']);
    Warehouse::factory()->create(['name' => 'Transit Warehouse']);

    $action = new ExportWarehousesAction;
    $request = Mockery::mock(ExportWarehouseRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'Main',
        'branch_id' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $action->execute($request);

    $filename = 'warehouses_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    Excel::assertStored('exports/' . $filename, 'public');
});
