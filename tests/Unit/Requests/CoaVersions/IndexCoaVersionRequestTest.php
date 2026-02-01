<?php

use App\Http\Requests\CoaVersions\IndexCoaVersionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('coa-versions');

test('IndexCoaVersionRequest → authorize returns true', function () {
    $request = new IndexCoaVersionRequest();
    expect($request->authorize())->toBeTrue();
});

test('IndexCoaVersionRequest → rules allow optional filters', function () {
    $fy = \App\Models\FiscalYear::factory()->create();
    $data = [
        'search' => 'test',
        'status' => 'active',
        'fiscal_year_id' => $fy->id,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 20,
    ];

    $request = new IndexCoaVersionRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('IndexCoaVersionRequest → rules fail with invalid per_page', function () {
    $data = ['per_page' => 'invalid'];

    $request = new IndexCoaVersionRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('per_page'))->toBeTrue();
});
