<?php

use App\Actions\InventoryStocktakes\ExportInventoryStocktakesAction;
use App\Http\Requests\InventoryStocktakes\ExportInventoryStocktakeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('it exports inventory stocktakes and returns filename plus url', function () {
    Carbon::setTestNow('2024-01-01 09:45:00');
    Excel::fake();
    Storage::fake('public');

    $request = mock(ExportInventoryStocktakeRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'search' => 'ink',
        'warehouse_id' => 2,
        'product_category_id' => 4,
        'status' => 'completed',
        'stocktake_date_from' => '2024-01-01',
        'stocktake_date_to' => '2024-01-31',
        'sort_by' => 'created_at',
        'sort_direction' => 'asc',
    ]);

    $action = new ExportInventoryStocktakesAction;

    $response = $action->execute($request);
    $data = $response->getData(true);

    $expectedFilename = 'inventory_stocktakes_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe($expectedFilename);

    Excel::assertStored('exports/' . $expectedFilename, 'public');
});
