<?php

use App\Http\Requests\AccountMappings\UpdateAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('UpdateAccountMappingRequest → authorize returns true', function () {
    $request = new UpdateAccountMappingRequest();
    expect($request->authorize())->toBeTrue();
});

test('UpdateAccountMappingRequest → rules contains expected fields', function () {
    $rules = (new UpdateAccountMappingRequest())->rules();

    expect($rules)->toHaveKeys(['source_account_id', 'target_account_id', 'type', 'notes']);
});
