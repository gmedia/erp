<?php

use App\Http\Requests\CoaVersions\UpdateCoaVersionRequest;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('coa-versions');

test('UpdateCoaVersionRequest → authorize returns true', function () {
    $request = new UpdateCoaVersionRequest();
    expect($request->authorize())->toBeTrue();
});

test('UpdateCoaVersionRequest → rules returns valid definitions', function () {
    $rules = (new UpdateCoaVersionRequest())->rules();

    expect($rules['name'])->toContain('required', 'string', 'max:255')
        ->and($rules['fiscal_year_id'])->toContain('required', 'integer', 'exists:fiscal_years,id')
        ->and($rules['status'])->toContain('required', 'string', 'in:draft,active,archived');
});

test('UpdateCoaVersionRequest → validation passes with valid data', function () {
    $fy = FiscalYear::factory()->create();
    $data = [
        'name' => 'Updated Version',
        'fiscal_year_id' => $fy->id,
        'status' => 'active',
    ];

    $request = new UpdateCoaVersionRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('UpdateCoaVersionRequest → validation ignores current version for unique name', function () {
    $fy = FiscalYear::factory()->create();
    $version = CoaVersion::factory()->create([
        'name' => 'Unique Version',
        'fiscal_year_id' => $fy->id,
    ]);

    // Mock the route parameter
    $request = new UpdateCoaVersionRequest();
    $request->setRouteResolver(function () use ($version) {
        $route = Mockery::mock();
        $route->shouldReceive('parameter')->with('coa_version', Mockery::any())->andReturn($version->id);
        return $route;
    });

    $data = [
        'name' => 'Unique Version',
        'fiscal_year_id' => $fy->id,
        'status' => 'active',
    ];

    $request->merge($data);
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});
