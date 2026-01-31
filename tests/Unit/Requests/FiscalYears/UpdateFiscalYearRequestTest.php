<?php

use App\Http\Requests\FiscalYears\UpdateFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('fiscal-years');

test('update rules are correct', function () {
    $fiscalYear = FiscalYear::factory()->create();
    
    $request = new UpdateFiscalYearRequest();
    // In a real request, the route parameter would be present.
    // For unit testing rules, we just check the presence of keys.
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'start_date', 'end_date', 'status']);
});

test('validation fails with invalid status', function () {
    $rules = (new UpdateFiscalYearRequest())->rules();
    $validator = Validator::make(['status' => 'invalid'], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});
