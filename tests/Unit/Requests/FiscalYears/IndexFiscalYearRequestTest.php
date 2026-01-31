<?php

use App\Http\Requests\FiscalYears\IndexFiscalYearRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('fiscal-years');

test('index rules are correct', function () {
    $request = new IndexFiscalYearRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['search', 'status', 'sort_by', 'sort_direction', 'per_page', 'page']);
});

test('validation passes with valid sort fields', function () {
    $validSorts = ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    $rules = (new IndexFiscalYearRequest())->rules();

    foreach ($validSorts as $field) {
        $validator = Validator::make(['sort_by' => $field], $rules);
        expect($validator->passes())->toBeTrue("Validation failed for sort_by: {$field}");
    }
});
