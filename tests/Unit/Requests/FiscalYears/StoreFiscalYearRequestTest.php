<?php

use App\Http\Requests\FiscalYears\StoreFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('fiscal-years');

test('store rules have required fields', function () {
    $rules = (new StoreFiscalYearRequest)->rules();

    expect($rules['name'])->toContain('required')
        ->and($rules['start_date'])->toContain('required')
        ->and($rules['end_date'])->toContain('required')
        ->and($rules['status'])->toContain('required');
});

test('validation fails with missing fields', function () {
    $rules = (new StoreFiscalYearRequest)->rules();
    $validator = Validator::make([], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('name', 'start_date', 'end_date', 'status');
});

test('validation fails when name is not unique', function () {
    FiscalYear::factory()->create(['name' => 'FY 2025']);

    $validator = Validator::make([
        'name' => 'FY 2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ], (new StoreFiscalYearRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});
