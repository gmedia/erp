<?php

namespace Tests\Unit\Actions\Assets;

use App\Actions\Assets\ExportAssetsAction;
use App\Http\Requests\Assets\ExportAssetRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('assets');

test('export assets action returns json with url', function () {
    Excel::fake();
    Storage::fake('public');
    
    $request = new ExportAssetRequest();
    
    // FormRequest::validated() calls $this->validator->validated()
    // We need to set a validator on the request
    $validator = Validator::make([], $request->rules());
    $validator->passes(); // Run validation so it has validated data
    $request->setValidator($validator);
    
    $action = new ExportAssetsAction();
    
    $response = $action->execute($request);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($data)->toHaveKeys(['url', 'filename']);
});
