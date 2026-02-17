<?php

use App\Http\Resources\Accounts\AccountCollection;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

test('it returns collection of resources', function () {
    $coaVersion = CoaVersion::factory()->create();
    
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => 'ACC001',
        'name' => 'Account 1',
    ]);
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => 'ACC002',
        'name' => 'Account 2',
    ]);
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => 'ACC003',
        'name' => 'Account 3',
    ]);
    
    $accounts = Account::all();

    $collection = new AccountCollection($accounts);
    $data = $collection->toArray(request());

    expect($data['data'])->toHaveCount(3)
        ->and($data['meta']['count'])->toBe(3);
    
    // Check first item data
    $firstItem = $data['data'][0]->toArray(request());
    expect($firstItem)->toHaveKey('id');
});
