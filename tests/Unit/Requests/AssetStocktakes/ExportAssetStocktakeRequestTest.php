<?php

use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeRequest;

uses()->group('asset-stocktakes');

test('export asset stocktake request authorizes access', function () {
    $request = new ExportAssetStocktakeRequest;

    expect($request->authorize())->toBeTrue();
});

test('export asset stocktake request returns correct validation rules', function () {
    $request = new ExportAssetStocktakeRequest;

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'branch' => ['nullable', 'exists:branches,id'],
        'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
        'sort_by' => ['nullable', 'string', 'in:id,reference,planned_at,performed_at,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
    ]);
});
