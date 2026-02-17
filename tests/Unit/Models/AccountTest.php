<?php

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

test('it has fillable attributes', function () {
    $data = [
        'coa_version_id' => 1,
        'parent_id' => null,
        'code' => '11000',
        'name' => 'Cash',
        'type' => 'asset',
        'sub_type' => null,
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
        'description' => 'Business cash',
    ];

    $account = new Account($data);

    foreach ($data as $key => $value) {
        expect($account->$key)->toBe($value);
    }
});

test('it belongs to coa version', function () {
    $coaVersion = CoaVersion::factory()->create();
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

    expect($account->coaVersion)->toBeInstanceOf(CoaVersion::class)
        ->and($account->coaVersion->id)->toBe($coaVersion->id);
});

test('it can have parent and children', function () {
    $parent = Account::factory()->create();
    $child = Account::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children->contains($child))->toBeTrue();
});
