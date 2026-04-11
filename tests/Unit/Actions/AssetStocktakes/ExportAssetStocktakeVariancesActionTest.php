<?php

use App\Actions\AssetStocktakes\ExportAssetStocktakeVariancesAction;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeVarianceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('it exports asset stocktake variances and preserves the legacy filename format', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-04 15:30:45'));
    Excel::fake();
    Storage::fake('public');

    $request = mock(ExportAssetStocktakeVarianceRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([
        'asset_stocktake_id' => 10,
        'branch_id' => 5,
        'result' => 'damaged',
        'search' => 'laptop',
    ]);

    $action = new ExportAssetStocktakeVariancesAction;

    $response = $action->execute($request);
    $data = $response->getData(true);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe('asset_stocktake_variances_20260404_153045.xlsx');

    Excel::assertStored('exports/asset_stocktake_variances_20260404_153045.xlsx', 'public');
    Carbon::setTestNow();
});
