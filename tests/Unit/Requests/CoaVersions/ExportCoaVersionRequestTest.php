<?php

use App\Http\Requests\CoaVersions\ExportCoaVersionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('coa-versions');

test('ExportCoaVersionRequest → authorize returns true', function () {
    $request = new ExportCoaVersionRequest();
    expect($request->authorize())->toBeTrue();
});

test('ExportCoaVersionRequest → rules allow valid filters', function () {
    $fy = \App\Models\FiscalYear::factory()->create();
    $data = [
        'search' => 'test',
        'status' => 'active',
        'fiscal_year_id' => $fy->id,
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ];

    $request = new ExportCoaVersionRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('ExportCoaVersionRequest → rules fail with invalid sort_direction', function () {
    $data = ['sort_direction' => 'invalid'];

    $request = new ExportCoaVersionRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('sort_direction'))->toBeTrue();
});
