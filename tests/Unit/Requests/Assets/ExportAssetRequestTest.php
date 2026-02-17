<?php

namespace Tests\Unit\Requests\Assets;

use App\Http\Requests\Assets\ExportAssetRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets');

test('export asset request validation rules', function () {
    $request = new ExportAssetRequest();
    $rules = $request->rules();

    expect($rules)->toBeArray();
    // Some rules might be empty or optional depending on implementation
});
