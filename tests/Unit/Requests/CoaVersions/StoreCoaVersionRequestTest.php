<?php

use App\Http\Requests\CoaVersions\StoreCoaVersionRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('coa-versions');

test('StoreCoaVersionRequest → authorize returns true', function () {
    $request = new StoreCoaVersionRequest();
    expect($request->authorize())->toBeTrue();
});

test('StoreCoaVersionRequest → rules returns valid definitions', function () {
    $rules = (new StoreCoaVersionRequest())->rules();

    expect($rules['name'])->toContain('required', 'string', 'max:255')
        ->and($rules['fiscal_year_id'])->toContain('required', 'integer', 'exists:fiscal_years,id')
        ->and($rules['status'])->toContain('required', 'string', 'in:draft,active,archived');
});

test('StoreCoaVersionRequest → validation passes with valid data', function () {
    $fy = FiscalYear::factory()->create();
    $data = [
        'name' => 'New Version',
        'fiscal_year_id' => $fy->id,
        'status' => 'draft',
    ];

    $request = new StoreCoaVersionRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('StoreCoaVersionRequest → validation fails with invalid status', function () {
    $fy = FiscalYear::factory()->create();
    $data = [
        'name' => 'New Version',
        'fiscal_year_id' => $fy->id,
        'status' => 'invalid-status',
    ];

    $request = new StoreCoaVersionRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue();
});

test('StoreCoaVersionRequest → validation fails when unique name in same fiscal year', function () {
    $fy = FiscalYear::factory()->create();
    \App\Models\CoaVersion::factory()->create([
        'name' => 'Existing Version',
        'fiscal_year_id' => $fy->id,
    ]);

    $data = [
        'name' => 'Existing Version',
        'fiscal_year_id' => $fy->id,
        'status' => 'active',
    ];

    $request = new StoreCoaVersionRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});
