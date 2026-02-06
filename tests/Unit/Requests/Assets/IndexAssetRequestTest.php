<?php

namespace Tests\Unit\Requests\Assets;

use App\Http\Requests\Assets\IndexAssetRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('assets', 'asset-unit');

test('index asset request validation rules', function () {
    $request = new IndexAssetRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['search', 'status', 'branch_id']);
});
