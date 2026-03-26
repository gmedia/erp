<?php

use App\Actions\AssetStocktakes\ExportAssetStocktakesAction;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('it exports asset stocktakes and returns filename plus url', function () {
    Carbon::setTestNow('2024-01-01 08:30:00');
    Excel::fake();
    Storage::fake('public');

    $request = mock(ExportAssetStocktakeRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'search' => 'printer',
        'branch' => 1,
        'status' => 'draft',
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $action = new ExportAssetStocktakesAction;

    $response = $action->execute($request);
    $data = $response->getData(true);

    $expectedFilename = 'asset_stocktakes_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe($expectedFilename);

    Excel::assertStored('exports/' . $expectedFilename, 'public');
});
