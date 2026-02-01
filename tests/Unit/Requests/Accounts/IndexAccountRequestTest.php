<?php

use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('accounts', 'requests');

test('it validates coa_version_id is required if present', function () {
    $request = new IndexAccountRequest();
    $validator = Validator::make(['coa_version_id' => 'abc'], $request->rules());
    
    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKey('coa_version_id');
});

test('it allows null or empty search', function () {
    $request = new IndexAccountRequest();
    $coaVersion = CoaVersion::factory()->create();
    $validator = Validator::make(['coa_version_id' => $coaVersion->id, 'search' => ''], $request->rules());
    
    expect($validator->passes())->toBeTrue();
});
