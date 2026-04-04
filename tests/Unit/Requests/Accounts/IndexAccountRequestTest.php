<?php

use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('accounts');

test('it authorizes account index access', function () {
    $request = new IndexAccountRequest;

    expect($request->authorize())->toBeTrue();
});

test('it validates coa_version_id is required if present', function () {
    $request = new IndexAccountRequest;
    $validator = Validator::make(['coa_version_id' => 'abc'], $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKey('coa_version_id');
});

test('it allows null or empty search', function () {
    $request = new IndexAccountRequest;
    $coaVersion = CoaVersion::factory()->create();
    $validator = Validator::make(['coa_version_id' => $coaVersion->id, 'search' => ''], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('it validates sort_order and per_page fields', function () {
    $request = new IndexAccountRequest;
    $validator = Validator::make([
        'sort_by' => 'name',
        'sort_order' => 'desc',
        'per_page' => 50,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});
