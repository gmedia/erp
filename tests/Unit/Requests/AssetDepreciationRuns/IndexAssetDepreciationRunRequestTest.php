<?php

use App\Http\Requests\AssetDepreciationRuns\IndexAssetDepreciationRunRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('asset-depreciation-runs');

test('asset depreciation run request authorizes access', function () {
    $request = new IndexAssetDepreciationRunRequest;

    expect($request->authorize())->toBeTrue();
});

test('asset depreciation run request validates listing filters', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $request = new IndexAssetDepreciationRunRequest;

    $validator = Validator::make([
        'search' => 'APR-001',
        'fiscal_year_id' => $fiscalYear->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'status' => 'calculated',
        'sort_by' => 'period_start',
        'sort_direction' => 'asc',
        'per_page' => 25,
        'page' => 1,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('asset depreciation run request rejects invalid status', function () {
    $request = new IndexAssetDepreciationRunRequest;
    $validator = Validator::make([
        'status' => 'processing',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue();
});
