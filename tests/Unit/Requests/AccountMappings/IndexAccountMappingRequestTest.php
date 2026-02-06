<?php

use App\Http\Requests\AccountMappings\IndexAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('IndexAccountMappingRequest → authorize returns true', function () {
    $request = new IndexAccountMappingRequest();
    expect($request->authorize())->toBeTrue();
});

test('IndexAccountMappingRequest → rules contains expected filters', function () {
    $rules = (new IndexAccountMappingRequest())->rules();

    expect($rules)->toHaveKeys([
        'search',
        'sort_by',
        'sort_direction',
        'per_page',
        'page',
        'type',
        'source_coa_version_id',
        'target_coa_version_id',
    ]);
});
