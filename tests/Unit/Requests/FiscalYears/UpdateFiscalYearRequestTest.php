<?php

use App\Http\Requests\FiscalYears\UpdateFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('fiscal-years');

test('update rules are correct', function () {
    $request = new UpdateFiscalYearRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'start_date', 'end_date', 'status']);
    expect($rules['name'])->toContain('sometimes', 'required', 'string', 'max:255');
    expect($rules['start_date'])->toContain('sometimes', 'required', 'date');
    expect($rules['end_date'])->toContain('sometimes', 'required', 'date', 'after:start_date');
    expect($rules['status'])->toContain('sometimes', 'required', 'in:open,closed,locked');
});

test('validation fails with invalid status', function () {
    $rules = (new UpdateFiscalYearRequest)->rules();
    $validator = Validator::make(['status' => 'invalid'], $rules);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('validation ignores current fiscal year for unique name', function () {
    $fiscalYear = FiscalYear::factory()->create(['name' => 'FY 2025']);

    $request = new UpdateFiscalYearRequest;
    $request->setRouteResolver(function () use ($fiscalYear) {
        $route = Mockery::mock();
        $route->shouldReceive('parameter')->with('fiscal_year', Mockery::any())->andReturn($fiscalYear);

        return $route;
    });

    $data = [
        'name' => 'FY 2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ];

    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});
