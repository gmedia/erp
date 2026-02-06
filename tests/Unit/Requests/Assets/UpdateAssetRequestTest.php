<?php

namespace Tests\Unit\Requests\Assets;

use App\Http\Requests\Assets\UpdateAssetRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets', 'asset-unit');

test('update asset request validation rules', function () {
    $request = new UpdateAssetRequest();
    $rules = $request->rules();
    
    expect($rules)->toHaveKey('asset_code')
        ->and($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('status');
});
