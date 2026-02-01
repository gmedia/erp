<?php

use App\Http\Requests\Accounts\ExportAccountRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('accounts', 'requests');

test('it validates required coa_version_id', function () {
    $request = new ExportAccountRequest();
    $validator = Validator::make([], $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKey('coa_version_id');
});

test('it validates type if provided', function () {
    $request = new ExportAccountRequest();
    $validator = Validator::make([
        'coa_version_id' => 1,
        'type' => 'invalid'
    ], $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKey('type');
});
