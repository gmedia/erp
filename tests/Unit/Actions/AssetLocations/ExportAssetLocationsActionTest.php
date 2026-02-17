<?php

use App\Actions\AssetLocations\ExportAssetLocationsAction;
use App\Http\Requests\AssetLocations\ExportAssetLocationRequest;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-locations');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');
    
    AssetLocation::factory()->count(3)->create();

    $action = new ExportAssetLocationsAction();
    $request = Mockery::mock(ExportAssetLocationRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'branch_id' => null,
        'parent_id' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    
    $result = $action->execute($request);

    $filename = 'asset_locations_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);
        
    Excel::assertStored('exports/' . $filename, 'public');
});
