<?php

use App\Actions\AssetModels\ExportAssetModelsAction;
use App\Http\Requests\AssetModels\ExportAssetModelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-models');

test('export action stores excel file', function () {
    Excel::fake();
    Storage::fake('public');

    $action = app(ExportAssetModelsAction::class);
    $request = mock(ExportAssetModelRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([]);

    $response = $action->execute($request);
    $filename = $response->getData()->filename;
    
    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->url)->toBeString()
        ->and($response->getData()->filename)->toBeString();
        
    Excel::assertStored('exports/' . $filename, 'public');
});
