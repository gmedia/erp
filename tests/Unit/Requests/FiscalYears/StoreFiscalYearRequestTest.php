<?php

use App\Http\Requests\FiscalYears\StoreFiscalYearRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('fiscal-years');

test('store rules have required fields', function () {
    $rules = (new StoreFiscalYearRequest())->rules();

    expect($rules['name'])->toContain('required')
        ->and($rules['start_date'])->toContain('required')
        ->and($rules['end_date'])->toContain('required')
        ->and($rules['status'])->toContain('required');
});

test('validation fails with missing fields', function () {
    $rules = (new StoreFiscalYearRequest())->rules();
    $validator = Validator::make([], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('name', 'start_date', 'end_date', 'status');
});
