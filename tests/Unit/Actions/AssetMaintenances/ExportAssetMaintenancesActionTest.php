<?php

namespace Tests\Unit\Actions\AssetMaintenances;

use App\Actions\AssetMaintenances\ExportAssetMaintenancesAction;
use App\Http\Requests\AssetMaintenances\ExportAssetMaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it can download export file', function () {
    \Illuminate\Support\Carbon::setTestNow('2023-01-01 00:00:00');
    Excel::fake();

    $request = Mockery::mock(ExportAssetMaintenanceRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $action = app(ExportAssetMaintenancesAction::class);

    $response = $action->execute($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);

    Excel::assertStored('exports/asset-maintenances-export-2023-01-01-00-00-00.xlsx', 'public');
});
