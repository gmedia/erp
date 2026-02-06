<?php

use App\Http\Requests\AccountMappings\ExportAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('ExportAccountMappingRequest → authorize returns true', function () {
    $request = new ExportAccountMappingRequest();
    expect($request->authorize())->toBeTrue();
});

test('ExportAccountMappingRequest → rules contains expected filters', function () {
    $rules = (new ExportAccountMappingRequest())->rules();

    expect($rules)->toHaveKeys([
        'search',
        'sort_by',
        'sort_direction',
        'type',
        'source_coa_version_id',
        'target_coa_version_id',
    ]);
});

