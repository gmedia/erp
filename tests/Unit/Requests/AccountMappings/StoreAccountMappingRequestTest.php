<?php

use App\Http\Requests\AccountMappings\StoreAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('StoreAccountMappingRequest → authorize returns true', function () {
    $request = new StoreAccountMappingRequest();
    expect($request->authorize())->toBeTrue();
});

test('StoreAccountMappingRequest → rules contains expected fields', function () {
    $rules = (new StoreAccountMappingRequest())->rules();

    expect($rules)->toHaveKeys(['source_account_id', 'target_account_id', 'type', 'notes']);
});
