<?php

use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Route;

uses(RefreshDatabase::class)->group('accounts', 'requests');

test('it validates required fields', function () {
    $coaVersion = CoaVersion::factory()->create();
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

    $request = new UpdateAccountRequest();
    $request->setRouteResolver(function () use ($account) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('account', Mockery::any())->andReturn($account);
        return $mockRoute;
    });

    $validator = Validator::make([], $request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->toArray())->toHaveKeys(['coa_version_id', 'code']);
});

test('it passes with valid data', function () {
    $coaVersion = CoaVersion::factory()->create();
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);
    
    $data = [
        'coa_version_id' => $coaVersion->id,
        'code' => '11001',
        'name' => 'Updated Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ];

    $request = new UpdateAccountRequest();
    $request->merge($data);
    $request->setRouteResolver(function() use ($account) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('account', Mockery::any())->andReturn($account);
        return $mockRoute;
    });

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});
