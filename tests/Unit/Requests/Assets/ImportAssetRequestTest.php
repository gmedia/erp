<?php

namespace Tests\Unit\Requests\Assets;

use App\Http\Requests\Assets\ImportAssetRequest;

uses()->group('assets');

test('import asset request authorizes access', function () {
    $request = new ImportAssetRequest;

    expect($request->authorize())->toBeTrue();
});

test('import asset request returns correct validation rules', function () {
    $request = new ImportAssetRequest;

    expect($request->rules())->toEqual([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
    ]);
});
