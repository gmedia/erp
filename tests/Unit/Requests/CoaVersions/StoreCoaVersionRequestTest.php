<?php

use App\Http\Requests\CoaVersions\StoreCoaVersionRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('coa-versions');

test('store rules have required fields', function () {
    $rules = (new StoreCoaVersionRequest())->rules();

    expect($rules['name'])->toContain('required')
        ->and($rules['fiscal_year_id'])->toContain('required')
        ->and($rules['status'])->toContain('required');
});

test('validation fails with missing fields', function () {
    $rules = (new StoreCoaVersionRequest())->rules();
    $validator = Validator::make([], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('name', 'fiscal_year_id', 'status');
});
