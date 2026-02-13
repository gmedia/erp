<?php

namespace Tests\Unit\Actions\AssetMovements;

use App\Actions\AssetMovements\ExportAssetMovementsAction;
use App\Http\Requests\AssetMovements\ExportAssetMovementRequest;
use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

test('it can download export file', function () {
    \Illuminate\Support\Carbon::setTestNow('2023-01-01 00:00:00');
    Excel::fake();

    $request = Mockery::mock(ExportAssetMovementRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    
    /** @var ExportAssetMovementsAction $action */
    $action = app(ExportAssetMovementsAction::class);
    
    $response = $action->execute($request);
    
    expect($response)->toBeInstanceOf(JsonResponse::class);
    
    Excel::assertStored('exports/asset-movements-export-' . now()->format('Y-m-d-H-i-s') . '.xlsx', 'public');
});
