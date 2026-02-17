<?php

use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('accounts');

test('it validates required fields', function () {
    $request = new StoreAccountRequest();
    $validator = Validator::make([], $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKeys(['coa_version_id', 'code', 'name', 'type']);
});

test('it passes with valid data', function () {
    $coaVersion = CoaVersion::factory()->create();
    $data = [
        'coa_version_id' => $coaVersion->id,
        'code' => '11000',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
    ];

    $request = new StoreAccountRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});
