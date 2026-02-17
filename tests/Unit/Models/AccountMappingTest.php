<?php

use App\Models\Account;
use App\Models\AccountMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('it has fillable attributes', function () {
    $data = [
        'source_account_id' => 1,
        'target_account_id' => 2,
        'type' => 'rename',
        'notes' => 'test',
    ];

    $mapping = new AccountMapping($data);

    foreach ($data as $key => $value) {
        expect($mapping->$key)->toBe($value);
    }
});

test('it belongs to source and target account', function () {
    $source = Account::factory()->create();
    $target = Account::factory()->create();

    $mapping = AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'test',
    ]);

    expect($mapping->sourceAccount)->toBeInstanceOf(Account::class)
        ->and($mapping->sourceAccount->id)->toBe($source->id)
        ->and($mapping->targetAccount)->toBeInstanceOf(Account::class)
        ->and($mapping->targetAccount->id)->toBe($target->id);
});

