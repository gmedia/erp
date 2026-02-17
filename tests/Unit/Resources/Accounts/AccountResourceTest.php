<?php

use App\Http\Resources\Accounts\AccountResource;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

test('it formats account data', function () {
    $account = Account::factory()->create([
        'code' => '11000',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $resource = new AccountResource($account);
    $data = $resource->toArray(request());

    expect($data['id'])->toBe($account->id)
        ->and($data['code'])->toBe('11000')
        ->and($data['name'])->toBe('Cash')
        ->and($data['type'])->toBe('asset')
        ->and($data['normal_balance'])->toBe('debit')
        ->and((bool)$data['is_active'])->toBeTrue();
});
